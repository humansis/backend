<?php

namespace Tests\NewApiBundle\Controller;

use NewApiBundle\Entity\Beneficiary;
use NewApiBundle\DataFixtures\VendorFixtures;
use NewApiBundle\Entity\Assistance;
use NewApiBundle\Entity\AssistanceBeneficiary;
use NewApiBundle\Component\Smartcard\SmartcardDepositService;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Enum\ModalityType;
use NewApiBundle\InputType\Smartcard\ChangeSmartcardInputType;
use NewApiBundle\InputType\Smartcard\SmartcardRegisterInputType;
use Tests\BMSServiceTestCase;
use NewApiBundle\Entity\User;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardDeposit;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Enum\SmartcardStates;
use VoucherBundle\Repository\SmartcardRepository;
use VoucherBundle\Utils\SmartcardService;

class SmartcardControllerTest extends BMSServiceTestCase
{

    public function setUp(): void
    {
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);
    }

    protected function tearDown(): void
    {
        $this->removeSmartcards('1234ABC');

        parent::tearDown();
    }

    private function removeSmartcards(string $serialNumber): void
    {
        $smartcards = $this->em->getRepository(Smartcard::class)->findBy(['serialNumber' => $serialNumber], ['id' => 'asc']);
        foreach ($smartcards as $smartcard) {
            $this->em->remove($smartcard);
        }
        $this->em->flush();
    }

    public function testRegisterSmartcard()
    {
        $this->removeSmartcards('1111111');
        $bnf = $this->em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'asc']);

        $this->request('POST', '/api/wsse/offline-app/v1/smartcards', [
            'serialNumber' => '1111111',
            'beneficiaryId' => $bnf->getId(), // @todo replace for fixture
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumberAndBeneficiary('1111111', $bnf);
        $this->em->remove($smartcard);
        $this->em->flush();
    }

    /**
     * @return void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testRegisterSmartcardDuplicity(): void
    {
        $smartcardService = self::$container->get(SmartcardService::class);
        $code = '1111111';
        $createdAt = '2005-02-02T12:00:00Z';
        $this->removeSmartcards($code);
        $bnf = $this->em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'asc']);
        $registerInputType = SmartcardRegisterInputType::create($code, $bnf->getId(), $createdAt);
        $smartcard = $smartcardService->register($registerInputType);

        $this->request('POST', '/api/wsse/offline-app/v1/smartcards', [
            'serialNumber' => $code,
            'beneficiaryId' => $bnf->getId(),
            'createdAt' => $createdAt,
        ]);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->em->remove($smartcard);
        $this->em->flush();
    }

    public function testRegisterDuplicateSmartcard()
    {
        $bnf = $this->em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'asc']);

        $this->request('POST', '/api/wsse/offline-app/v1/smartcards', [
            'serialNumber' => '1234ABC',
            'beneficiaryId' => $bnf->getId(), // @todo replace for fixture
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request should failed: '.$this->client->getResponse()->getContent());
    }

    public function testPurchase()
    {
        $depositor = $this->em->getRepository(User::class)->findOneBy([], ['id' => 'asc']);
        $assistanceBeneficiary = $this->assistanceBeneficiaryWithoutRelief();
        $bnf = $assistanceBeneficiary->getBeneficiary();
        $assistance = $assistanceBeneficiary->getAssistance();

        $smartcard = $this->getSmartcardForBeneficiary('1234ABC', $bnf);

        $reliefPackage = $this->createReliefPackage($assistanceBeneficiary);

        $date = new \DateTime('now');
        $hash = SmartcardDepositService::generateDepositHash($smartcard->getSerialNumber(), $date->getTimestamp(), 600, $reliefPackage);
        $deposit = SmartcardDeposit::create($smartcard, $depositor, $reliefPackage, 600, null, $date, $hash);
        $smartcard->addDeposit($deposit);

        $this->em->persist($smartcard);
        $this->em->flush();

        $headers = ['HTTP_COUNTRY' => 'KHM'];
        $content = json_encode([
            'products' => [
                [
                    'id' => 1, // @todo replace for fixture
                    'value' => 300.25,
                    'quantity' => 1.2,
                    'currency' => 'USD',
                ],
            ],
            'vendorId' => 1,
            'beneficiaryId' => $bnf->getId(),
            'createdAt' => '2020-02-02T11:11:11Z',
            'assistanceId' => $assistance->getId(),
        ]);

        $this->client->request('POST', '/api/wsse/vendor-app/v4/smartcards/'.$smartcard->getSerialNumber().'/purchase', [], [], $headers, $content);

        $smartcard = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('value', $smartcard);
        $this->assertEquals(299.75, $smartcard['value'], 0.0001);
    }

    public function testPurchaseV4()
    {
        $vendor = $this->em->getRepository(Vendor::class)->findOneBy([
            'name' => VendorFixtures::VENDOR_SYR_NAME,
        ], ['id' => 'asc']);
        $depositor = $this->em->getRepository(User::class)->findOneBy([], ['id' => 'asc']);
        $assistanceBeneficiary = $this->assistanceBeneficiaryWithoutRelief();
        $bnf = $assistanceBeneficiary->getBeneficiary();

        $smartcard = $this->getSmartcardForBeneficiary('1234ABC', $bnf);

        $reliefPackage = $this->createReliefPackage($assistanceBeneficiary);

        $date = new \DateTime('now');
        $hash = SmartcardDepositService::generateDepositHash($smartcard->getSerialNumber(), $date->getTimestamp(), 600, $reliefPackage);
        $deposit = SmartcardDeposit::create($smartcard, $depositor, $reliefPackage, 600, null, $date, $hash);
        $smartcard->addDeposit($deposit);

        $this->em->persist($smartcard);
        $this->em->flush();

        $headers = ['HTTP_COUNTRY' => 'KHM'];
        $content = json_encode([
            'products' => [
                [
                    'id' => 1, // @todo replace for fixture
                    'value' => 300.25,
                    'currency' => 'USD',
                ],
            ],
            'vendorId' => $vendor->getId(),
            'beneficiaryId' => $bnf->getId(),
            'assistanceId' => $assistanceBeneficiary->getAssistance()->getId(),
            'createdAt' => '2020-02-02T12:11:11Z',
            'balanceBefore' => 50,
            'balanceAfter' => 20,
        ]);

        $this->client->request('POST', '/api/wsse/vendor-app/v4/smartcards/'.$smartcard->getSerialNumber().'/purchase', [], [], $headers, $content);

        $smartcard = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('value', $smartcard);
        $this->assertEquals(299.75, $smartcard['value'], 0.0001);
    }

    /**
     * It must be allow to make payment from blocked or empty smartcard - due to latency between payment and write to system.
     */
    public function testPurchaseFromEmptySmartcard()
    {
        $depositor = $this->em->getRepository(User::class)->findOneBy([], ['id' => 'asc']);
        $assistanceBeneficiary = $this->assistanceBeneficiaryWithoutRelief();
        $bnf = $assistanceBeneficiary->getBeneficiary();
        $assistance = $assistanceBeneficiary->getAssistance();

        $reliefPackage = $this->createReliefPackage($assistanceBeneficiary);

        $smartcard = $this->getSmartcardForBeneficiary('1234ABC', $bnf);
        $smartcard->setState(SmartcardStates::INACTIVE);
        $date = new \DateTime('now');
        $hash = SmartcardDepositService::generateDepositHash($smartcard->getSerialNumber(), $date->getTimestamp(), 100, $reliefPackage);
        $smartcard->addDeposit(SmartcardDeposit::create($smartcard, $depositor, $reliefPackage, 100, null, $date, $hash));

        $this->em->persist($smartcard);
        $this->em->flush();

        $headers = ['HTTP_COUNTRY' => 'KHM'];
        $content = json_encode([
            'products' => [
                [
                    'id' => 1, // @todo replace for fixture
                    'value' => 400,
                    'quantity' => 1.2,
                    'currency' => 'USD',
                ],
            ],
            'vendorId' => 1,
            'beneficiaryId' => $bnf->getId(),
            'createdAt' => '2020-02-02T12:00:00Z',
            'assistanceId' => $assistance->getId(),
        ]);

        $this->client->request('POST', '/api/wsse/vendor-app/v4/smartcards/'.$smartcard->getSerialNumber().'/purchase', [], [], $headers, $content);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
    }

    /**
     * It must be allow to make payment from blocked or empty smartcard - due to latency between payment and write to system.
     */
    public function testPurchaseFromEmptySmartcardV4()
    {
        /** @var Assistance|null $assistance */
        $assistance = $this->em->getRepository(Assistance::class)->findOneBy([], ['id' => 'asc']);
        if(!$assistance){
            $this->markTestSkipped('There is no assistance in database.');
        }

        $depositor = $this->em->getRepository(User::class)->findOneBy([], ['id' => 'asc']);
        $assistanceBeneficiary = $this->assistanceBeneficiaryWithoutRelief();
        $bnf = $assistanceBeneficiary->getBeneficiary();

        $reliefPackage = $this->createReliefPackage($assistanceBeneficiary);

        $smartcard = $this->getSmartcardForBeneficiary('1234ABC', $bnf);
        $smartcard->setState(SmartcardStates::INACTIVE);
        $date = new \DateTime('now');
        $hash = SmartcardDepositService::generateDepositHash($smartcard->getSerialNumber(), $date->getTimestamp(), 100, $reliefPackage);
        $smartcard->addDeposit(SmartcardDeposit::create($smartcard, $depositor, $reliefPackage, 100, null, $date, $hash));

        $this->em->persist($smartcard);
        $this->em->flush();

        $headers = ['HTTP_COUNTRY' => 'KHM'];
        $content = json_encode([
            'products' => [
                [
                    'id' => 1, // @todo replace for fixture
                    'value' => 400,
                    'quantity' => 1.2,
                    'currency' => 'USD',
                ],
            ],
            'vendorId' => 1,
            'beneficiaryId' => $bnf->getId(),
            'createdAt' => '2020-02-02T13:01:00Z',
            'assistanceId' => $assistance->getId(),
        ]);

        $this->client->request('POST', '/api/wsse/vendor-app/v4/smartcards/'.$smartcard->getSerialNumber().'/purchase', [], [], $headers, $content);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
    }

    public function testPurchaseShouldBeAllowedForNonexistentSmartcardV4()
    {
        /** @var Assistance|null $assistance */
        $assistance = $this->em->getRepository(Assistance::class)->findOneBy([], ['id' => 'asc']);
        if(!$assistance){
            $this->markTestSkipped('There is no assistance in database.');
        }

        $nonexistentSmarcard = '23456789012';
        $bnf = $this->em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'asc']);

        $headers = ['HTTP_COUNTRY' => 'KHM'];
        $content = json_encode([
            'products' => [
                [
                    'id' => 1, // @todo replace for fixture
                    'value' => 400,
                    'quantity' => 1.2,
                    'currency' => 'CZK',
                ],
            ],
            'vendorId' => 1,
            'beneficiaryId' => $bnf->getId(),
            'createdAt' => '2020-02-02T12:02:00Z',
            'balanceBefore' => 20,
            'balanceAfter' => 10,
            'assistanceId' => $assistance->getId(),
        ]);

        $this->client->request('POST', '/api/wsse/vendor-app/v4/smartcards/'.$nonexistentSmarcard.'/purchase', [], [], $headers, $content);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());

        /** @var Smartcard $smartcard */
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumberAndBeneficiary($nonexistentSmarcard, $bnf);

        $this->assertNotNull($smartcard, 'Smartcard must be registered to system');
        $this->assertTrue($smartcard->isSuspicious(), 'Smartcard registered by purchase must be suspected');
    }

    public function testChangeStateToInactive()
    {
        $smartcardRepository = self::$container->get(SmartcardRepository::class);
        $bnf = $this->em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'asc']);
        $smartcard = $this->getSmartcardForBeneficiary('1234ABC', $bnf);

        $this->request('PATCH', '/api/wsse/offline-app/v1/smartcards/'.$smartcard->getSerialNumber(), [
            'state' => SmartcardStates::INACTIVE,
            'createdAt' => '2020-02-02T12:00:00Z',
            'beneficiaryId' => $bnf->getId(),
        ]);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $smartcard = $smartcardRepository->find($smartcard->getId());
        $this->assertEquals(SmartcardStates::INACTIVE, $smartcard->getState());
    }

    public function testChangeStateToInactiveDoubled(): void
    {
        $smartcardService = self::$container->get(SmartcardService::class);
        $bnf = $this->em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'asc']);
        $smartcard = $this->getSmartcardForBeneficiary('1234ABC', $bnf);
        $date = '2005-02-02T12:00:00Z';
        $changeInputType = ChangeSmartcardInputType::create(SmartcardStates::INACTIVE, $date);
        $smartcardService->change($smartcard, $changeInputType);

        $this->request('PATCH', '/api/wsse/offline-app/v1/smartcards/'.$smartcard->getSerialNumber(), [
            'state' => SmartcardStates::INACTIVE,
            'createdAt' => $date,
            'beneficiaryId' => $bnf->getId(),
        ]);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertEquals(202, $this->client->getResponse()->getStatusCode());
    }

    /**
     * This is test of issue #PIN-1572: Synces are in wrong order.
     * Vendor performs sync purchases before Field officer performs sync smartcard deposit. In result, currency is missing.
     */
    public function testPurchasesShouldHaveCurrencyInNotPresentInRequestStep1()
    {
        $nonexistentSmarcard = '123ABCDE';

        foreach ($this->em->getRepository(Smartcard::class)->findBy(['serialNumber'=>$nonexistentSmarcard], ['id' => 'asc']) as $smartcard) {
            $this->em->remove($smartcard);
        }
        $this->em->flush();

        /** @var \NewApiBundle\Entity\ModalityType $modalityType */
        $modalityType = $this->em->getRepository(\NewApiBundle\Entity\ModalityType::class)->findOneBy(['name' => 'Smartcard'], ['id' => 'asc']);
        /** @var \NewApiBundle\Entity\Commodity $commodity */
        $commodity = $this->em->getRepository(\NewApiBundle\Entity\Commodity::class)->findBy(['modalityType' => $modalityType], ['id' => 'asc'])[0];
        $assistance = $commodity->getAssistance();
        $beneficiary = $assistance->getDistributionBeneficiaries()[0]->getBeneficiary();
        $assistance = $assistance->getDistributionBeneficiaries()[0]->getAssistance();

        $this->client->request('POST', '/api/wsse/vendor-app/v4/smartcards/'.$nonexistentSmarcard.'/purchase', [], [], ['HTTP_COUNTRY' => 'KHM'],
            json_encode([
                'products' => [
                    [
                        'id' => 1, // @todo replace for fixture
                        'value' => 200,
                        'quantity' => 1,
                        'currency' => 'USD',
                    ],
                ],
                'vendorId' => 1,
                'beneficiaryId' => $beneficiary->getId(),
                'createdAt' => '2020-02-02T11:11:00Z',
                'assistanceId' => $assistance->getId(),
            ]));

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());

        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumberAndBeneficiary($nonexistentSmarcard, $beneficiary);
        $this->assertNotNull($smartcard, "Smartcard missing");
        $value = 0;
        foreach ($smartcard->getPurchases() as $purchase) {
            foreach ($purchase->getRecords() as $record) {
                $value += $record->getValue();
            }
        }
        $this->assertCount(1, $smartcard->getPurchases());
        $this->assertEquals(200, $value);

        return [$nonexistentSmarcard, $assistance, $beneficiary];
    }

    /**
     * @depends testPurchasesShouldHaveCurrencyInNotPresentInRequestStep1
     */
    public function testPurchasesShouldHaveCurrencyInNotPresentInRequestStep2($array)
    {
        [$smartcard, $assistance, $beneficiary] = $array;

        $this->request('POST', '/api/wsse/offline-app/v1/smartcards', [
            'serialNumber' => $smartcard,
            'beneficiaryId' => $beneficiary->getId(),
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());

        return [$smartcard, $assistance, $beneficiary];
    }

    /**
     * @depends testPurchasesShouldHaveCurrencyInNotPresentInRequestStep2
     */
    public function testPurchasesShouldHaveCurrencyInNotPresentInRequestStep3($array)
    {
        $this->markTestSkipped('This should be re-factorized to current deposit endpoint.');

        [$nonexistentSmarcard, $distribution, $beneficiary] = $array;

        $assistanceBeneficiary = $this->em->getRepository(AssistanceBeneficiary::class)->findOneBy([
            'assistance' => $distribution,
            'beneficiary' => $beneficiary,
        ], ['id' => 'asc']);

        $reliefPackage = $this->createReliefPackage($assistanceBeneficiary);

        $this->request('PATCH', '/api/wsse/offline-app/v3/smartcards/'.$nonexistentSmarcard.'/deposit', [
            'value' => 500,
            'createdAt' => '2020-02-02T12:00:00+0001',
            'beneficiaryId' => $reliefPackage->getAssistanceBeneficiary()->getBeneficiary()->getId(),
            'distributionId' => $reliefPackage->getAssistanceBeneficiary()->getAssistance()->getId(),
        ]);

        /** @var Smartcard $smartcard */
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumberAndBeneficiary($nonexistentSmarcard, $beneficiary);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertNotNull($smartcard);
        $this->assertNotNull($smartcard->getCurrency());
        $this->assertNotNull($smartcard->getPurchases()[0]->getCurrency());
    }

    public function testDuplicityPurchase(): void
    {
        /** @var User $depositor */
        $depositor = $this->em->getRepository(User::class)->findOneBy([], ['id' => 'asc']);

        /** @var AssistanceBeneficiary $assistanceBeneficiary */
        $assistanceBeneficiary = $this->someSmartcardAssistance()->getDistributionBeneficiaries()->get(0);

        /** @var Beneficiary $bnf */
        $bnf = $this->em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'asc']);

        $assistance = $assistanceBeneficiary->getAssistance();

        $reliefPackage = $this->createReliefPackage($assistanceBeneficiary);

        $smartcard = $this->getSmartcardForBeneficiary('1234ABC', $bnf);
        $date = new \DateTime('now');
        $hash = SmartcardDepositService::generateDepositHash($smartcard->getSerialNumber(), $date->getTimestamp(), 600, $reliefPackage);
        $smartcard->addDeposit(SmartcardDeposit::create($smartcard, $depositor, $reliefPackage, 600, null, $date, $hash));

        $this->em->persist($smartcard);
        $this->em->flush();

        $headers = ['HTTP_COUNTRY' => 'KHM'];
        $requestBody = [
            'products' => [
                [
                    'id' => 1, // @todo replace for fixture
                    'value' => 300.25,
                    'quantity' => 1.2,
                    'currency' => 'USD',
                ],
            ],
            'vendorId' => 1,
            'beneficiaryId' => $bnf->getId(),
            'createdAt' => '2020-02-02T11:00:00Z',
            'assistanceId' => $assistance->getId(),
        ];

        //first request
        $this->client->request('POST', '/api/wsse/vendor-app/v4/smartcards/'.$smartcard->getSerialNumber().'/purchase', [], [], $headers,
            json_encode($requestBody));
        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('value', $content);
        $smartCardPurchasesCount1 = count($this->em->getRepository(SmartcardPurchase::class)->findAll());

        $this->setUp();

        //second request with the same bnfId+vendorId+createdAt
        $this->client->request('POST', '/api/wsse/vendor-app/v4/smartcards/'.$smartcard->getSerialNumber().'/purchase', [], [], $headers,
            json_encode($requestBody));
        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $cnt = $this->client->getResponse()->getContent();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('value', $content);
        $smartCardPurchasesCount2 = count($this->em->getRepository(SmartcardPurchase::class)->findAll());
        $this->assertEquals($smartCardPurchasesCount1, $smartCardPurchasesCount2);

        $this->setUp();

        //third request with same bnfId+vendorId+ createdAt+1second
        $requestBody['createdAt'] = '2020-02-02T11:00:01Z';
        $this->client->request('POST', '/api/wsse/vendor-app/v4/smartcards/'.$smartcard->getSerialNumber().'/purchase', [], [], $headers,
            json_encode($requestBody));
        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('value', $content);
        $smartCardPurchasesCount3 = count($this->em->getRepository(SmartcardPurchase::class)->findAll());
        $this->assertGreaterThan($smartCardPurchasesCount1, $smartCardPurchasesCount3);

        $this->setUp();

        //fourth request with same bnfId+vendorId+ createdAt+2seconds
        $requestBody['createdAt'] = '2020-02-02T11:00:02Z';
        $this->client->request('POST', '/api/wsse/vendor-app/v4/smartcards/'.$smartcard->getSerialNumber().'/purchase', [], [], $headers,
            json_encode($requestBody));
        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('value', $content);
        $smartCardPurchasesCount4 = count($this->em->getRepository(SmartcardPurchase::class)->findAll());
        $this->assertGreaterThan($smartCardPurchasesCount3, $smartCardPurchasesCount4);
        $this->assertGreaterThan($smartCardPurchasesCount1, $smartCardPurchasesCount4);

        $this->setUp();

        //fifth request with same bnfId+vendorId+ createdAt+2seconds
        $this->client->request('POST', '/api/wsse/vendor-app/v4/smartcards/'.$smartcard->getSerialNumber().'/purchase', [], [], $headers,
            json_encode($requestBody));
        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('value', $content);
        $smartCardPurchasesCount5 = count($this->em->getRepository(SmartcardPurchase::class)->findAll());
        $this->assertGreaterThan($smartCardPurchasesCount3, $smartCardPurchasesCount5);
        $this->assertEquals($smartCardPurchasesCount4, $smartCardPurchasesCount5);
    }

    private function someSmartcardAssistance(): ?Assistance
    {
        foreach ($this->em->getRepository(Assistance::class)->findAll() as $assistance) {
            foreach ($assistance->getCommodities() as $commodity) {
                if ('Smartcard' === $commodity->getModalityType()->getName()) {
                    return $assistance;
                }
            }
        }

        return null;
    }

    private function assistanceBeneficiaryWithoutRelief(): AssistanceBeneficiary
    {
        /** @var Assistance $assistance */
        foreach ($this->em->getRepository(Assistance::class)->findAll() as $assistance) {
            foreach ($assistance->getCommodities() as $commodity) {
                if (ModalityType::SMART_CARD !== $commodity->getModalityType()->getName()) {
                    continue 2;
                }
            }

            foreach ($assistance->getDistributionBeneficiaries() as $assistanceBeneficiary) {
                if ($assistanceBeneficiary->getReliefPackages()->isEmpty()) {
                    return $assistanceBeneficiary;
                }
            }
        }

        $assistanceBeneficiary = new AssistanceBeneficiary();
        $assistanceBeneficiary->setAssistance($this->someSmartcardAssistance());
        $assistanceBeneficiary->setBeneficiary($this->em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'asc']));

        $this->em->persist($assistanceBeneficiary);

        return $assistanceBeneficiary;
    }

    private function getSmartcardForBeneficiary(string $serialNumber, Beneficiary $beneficiary): Smartcard
    {
        /** @var Smartcard[] $smartcards */
        $smartcards = $this->em->getRepository(Smartcard::class)->findBy(['serialNumber' => $serialNumber], ['id' => 'asc']);

        foreach ($smartcards as $smartcard) {
            if ($smartcard->getState() === SmartcardStates::ACTIVE) {
                $smartcard->setBeneficiary($beneficiary);

                return $smartcard;
            }
        }

        $smartcard = new Smartcard($serialNumber, new \DateTime('now'));
        $smartcard->setBeneficiary($beneficiary);
        $smartcard->setState(SmartcardStates::ACTIVE);

        $this->em->persist($smartcard);
        $this->em->flush();

        return $smartcard;
    }

    private function createReliefPackage(AssistanceBeneficiary $ab): ReliefPackage
    {
        $reliefPackage = new ReliefPackage(
            $ab,
            ModalityType::SMART_CARD,
            $ab->getAssistance()->getCommodities()[0]->getValue(),
            $ab->getAssistance()->getCommodities()[0]->getUnit(),
        );

        $this->em->persist($reliefPackage);
        $this->em->flush();

        return $reliefPackage;
    }
}
