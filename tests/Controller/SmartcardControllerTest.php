<?php

namespace Tests\Controller;

use Component\Smartcard\Deposit\DepositFactory;
use DateTime;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Entity\Beneficiary;
use DataFixtures\VendorFixtures;
use Entity\Assistance;
use Entity\AssistanceBeneficiary;
use Entity\Assistance\ReliefPackage;
use Entity\Commodity;
use Enum\ModalityType;
use InputType\Smartcard\ChangeSmartcardInputType;
use InputType\Smartcard\DepositInputType;
use InputType\Smartcard\SmartcardRegisterInputType;
use Repository\BeneficiaryRepository;
use Tests\BMSServiceTestCase;
use Entity\User;
use Entity\SmartcardBeneficiary;
use Entity\SmartcardPurchase;
use Entity\Vendor;
use Enum\SmartcardStates;
use Repository\SmartcardBeneficiaryRepository;
use Tests\ComponentHelper\SmartcardHelper;
use Utils\SmartcardService;

class SmartcardControllerTest extends BMSServiceTestCase
{
    use SmartcardHelper;

    private DepositFactory $depositFactory;

    public function setUp(): void
    {
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::getContainer()->get('test.client');
        $this->depositFactory = self::getContainer()->get(DepositFactory::class);
    }

    protected function tearDown(): void
    {
        $this->removeSmartcards('1234ABC');

        parent::tearDown();
    }

    private function removeSmartcards(string $serialNumber): void
    {
        $smartcardBeneficiaries = $this->em->getRepository(SmartcardBeneficiary::class)->findBy(
            ['serialNumber' => $serialNumber],
            ['id' => 'asc']
        );
        foreach ($smartcardBeneficiaries as $smartcardBeneficiary) {
            $this->em->remove($smartcardBeneficiary);
        }
        $this->em->flush();
    }

    public function testRegisterSmartcard()
    {
        $this->removeSmartcards('1111111');
        $bnf = $this->em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'asc']);

        $this->request('POST', '/api/basic/offline-app/v1/smartcards', [
            'serialNumber' => '1111111',
            'beneficiaryId' => $bnf->getId(), // @todo replace for fixture
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertSame(\Symfony\Component\HttpFoundation\Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $smartcardBeneficiary = $this->em->getRepository(SmartcardBeneficiary::class)->findBySerialNumberAndBeneficiary('1111111', $bnf);
        $this->em->remove($smartcardBeneficiary);
        $this->em->flush();
    }

    public function testReRegisterSmartcard(): void
    {
        $this->em->beginTransaction();

        $smartcardBeneficiaryRepository = self::getContainer()->get(SmartcardBeneficiaryRepository::class);
        $firstSmartcardCode = '1111111';
        $secondSmartcardCode = '2222222';
        $bnf = self::getContainer()->get(BeneficiaryRepository::class)->findOneBy([], ['id' => 'asc']);
        $this->getSmartcardForBeneficiary($firstSmartcardCode, $bnf);

        $this->request('POST', '/api/basic/offline-app/v1/smartcards', [
            'serialNumber' => $secondSmartcardCode,
            'beneficiaryId' => $bnf->getId(),
            'createdAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        /**
         * @var SmartcardBeneficiary[] $activeSmartcardBeneficiaries
         */
        $activeSmartcardBeneficiaries = $smartcardBeneficiaryRepository->findBy(['beneficiary' => $bnf, 'state' => SmartcardStates::ACTIVE]);
        $this->assertEquals(1, count($activeSmartcardBeneficiaries));
        $this->assertEquals($secondSmartcardCode, $activeSmartcardBeneficiaries[0]->getSerialNumber());

        $this->em->rollback();
    }

    public function testRegisterOldCardAgain(): void
    {
        $this->em->beginTransaction();

        $smartcardBeneficiaryRepository = self::getContainer()->get(SmartcardBeneficiaryRepository::class);
        $firstSmartcardCode = '1111111';
        $secondSmartcardCode = '2222222';
        $bnf = self::getContainer()->get(BeneficiaryRepository::class)->findOneBy([], ['id' => 'asc']);
        $smartcardBeneficiary1 = $this->getSmartcardForBeneficiary($firstSmartcardCode, $bnf);
        $smartcardBeneficiary1->setState(SmartcardStates::ACTIVE);
        $smartcardBeneficiaryRepository->save($smartcardBeneficiary1);
        $smartcardBeneficiary2 = $this->getSmartcardForBeneficiary($secondSmartcardCode, $bnf);
        $smartcardBeneficiary2->setState(SmartcardStates::INACTIVE);
        $smartcardBeneficiaryRepository->save($smartcardBeneficiary2);

        $this->request('POST', '/api/basic/offline-app/v1/smartcards', [
            'serialNumber' => $secondSmartcardCode,
            'beneficiaryId' => $bnf->getId(),
            'createdAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $this->em->refresh($smartcardBeneficiary1);
        $this->em->refresh($smartcardBeneficiary2);
        $this->assertEquals(SmartcardStates::ACTIVE, $smartcardBeneficiary2->getState());
        $this->assertEquals(SmartcardStates::INACTIVE, $smartcardBeneficiary1->getState());

        $this->em->rollback();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testRegisterSmartcardDuplicity(): void
    {
        $smartcardService = self::getContainer()->get(SmartcardService::class);
        $code = '1111111';
        $createdAt = '2005-02-02T12:00:00Z';
        $this->removeSmartcards($code);
        $bnf = $this->em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'asc']);
        $registerInputType = SmartcardRegisterInputType::create($code, $bnf->getId(), DateTime::createFromFormat('Y-m-d\TH:i:sO', $createdAt));
        $smartcardBeneficiary = $smartcardService->register($registerInputType);

        $this->request('POST', '/api/basic/offline-app/v1/smartcards', [
            'serialNumber' => $code,
            'beneficiaryId' => $bnf->getId(),
            'createdAt' => $createdAt,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertSame(\Symfony\Component\HttpFoundation\Response::HTTP_ACCEPTED, $this->client->getResponse()->getStatusCode());

        $this->em->remove($smartcardBeneficiary);
        $this->em->flush();
    }

    public function testPurchase()
    {
        $depositor = $this->em->getRepository(User::class)->findOneBy([], ['id' => 'asc']);
        $assistanceBeneficiary = $this->assistanceBeneficiaryWithoutRelief();
        $bnf = $assistanceBeneficiary->getBeneficiary();
        $assistance = $assistanceBeneficiary->getAssistance();

        $smartcardBeneficiary = $this->getSmartcardForBeneficiary('1234ABC', $bnf);

        $reliefPackage = $this->createReliefPackage($assistanceBeneficiary);

        $date = new DateTime('now');
        $deposit = $this->depositFactory->create(
            $smartcardBeneficiary->getSerialNumber(),
            DepositInputType::create($reliefPackage->getId(), 600, null, $date),
            $depositor
        );
        $smartcardBeneficiary->addDeposit($deposit);

        $this->em->persist($smartcardBeneficiary);
        $this->em->flush();

        $headers = [
            'HTTP_COUNTRY' => 'KHM',
            'PHP_AUTH_USER' => 'admin@example.org',
            'PHP_AUTH_PW' => 'pin1234',
            'CONTENT_TYPE' => 'application/json',
        ];
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
        ], JSON_THROW_ON_ERROR);

        $this->client->request(
            'POST',
            '/api/wsse/vendor-app/v4/smartcards/' . $smartcardBeneficiary->getSerialNumber() . '/purchase',
            [],
            [],
            $headers,
            $content
        );

        $smartcardBeneficiary = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertArrayHasKey('value', $smartcardBeneficiary);
        $this->assertEquals(299.75, $smartcardBeneficiary['value']);
    }

    public function testPurchaseV4()
    {
        $vendor = $this->em->getRepository(Vendor::class)->findOneBy([
            'name' => VendorFixtures::VENDOR_SYR_NAME,
        ], ['id' => 'asc']);
        $depositor = $this->em->getRepository(User::class)->findOneBy([], ['id' => 'asc']);
        $assistanceBeneficiary = $this->assistanceBeneficiaryWithoutRelief();
        $bnf = $assistanceBeneficiary->getBeneficiary();

        $smartcardBeneficiary = $this->getSmartcardForBeneficiary('1234ABC', $bnf);

        $reliefPackage = $this->createReliefPackage($assistanceBeneficiary);

        $date = new DateTime('now');
        $deposit = $this->depositFactory->create(
            $smartcardBeneficiary->getSerialNumber(),
            DepositInputType::create($reliefPackage->getId(), 600, null, $date),
            $depositor
        );
        $smartcardBeneficiary->addDeposit($deposit);

        $this->em->persist($smartcardBeneficiary);
        $this->em->flush();

        $headers = [
            'HTTP_COUNTRY' => 'KHM',
            'PHP_AUTH_USER' => 'admin@example.org',
            'PHP_AUTH_PW' => 'pin1234',
            'CONTENT_TYPE' => 'application/json',
        ];
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
        ], JSON_THROW_ON_ERROR);

        $this->client->request(
            'POST',
            '/api/basic/vendor-app/v4/smartcards/' . $smartcardBeneficiary->getSerialNumber() . '/purchase',
            [],
            [],
            $headers,
            $content
        );

        $smartcardBeneficiary = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertArrayHasKey('value', $smartcardBeneficiary);
        $this->assertEquals(299.75, $smartcardBeneficiary['value'], 0.0001);
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

        $smartcardBeneficiary = $this->getSmartcardForBeneficiary('1234ABC', $bnf);
        $smartcardBeneficiary->setState(SmartcardStates::INACTIVE);
        $date = new DateTime('now');
        $smartcardBeneficiary->addDeposit(
            $this->depositFactory->create(
                $smartcardBeneficiary->getSerialNumber(),
                DepositInputType::create($reliefPackage->getId(), 100, null, $date),
                $depositor
            )
        );

        $this->em->persist($smartcardBeneficiary);
        $this->em->flush();

        $headers = [
            'HTTP_COUNTRY' => 'KHM',
            'PHP_AUTH_USER' => 'admin@example.org',
            'PHP_AUTH_PW' => 'pin1234',
            'CONTENT_TYPE' => 'application/json',
        ];
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
        ], JSON_THROW_ON_ERROR);

        $this->client->request(
            'POST',
            '/api/basic/vendor-app/v4/smartcards/' . $smartcardBeneficiary->getSerialNumber() . '/purchase',
            [],
            [],
            $headers,
            $content
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
    }

    /**
     * It must be allow to make payment from blocked or empty smartcard - due to latency between payment and write to system.
     */
    public function testPurchaseFromEmptySmartcardV4()
    {
        /** @var Assistance|null $assistance */
        $assistance = $this->em->getRepository(Assistance::class)->findOneBy([], ['id' => 'asc']);
        if (!$assistance) {
            $this->markTestSkipped('There is no assistance in database.');
        }

        $depositor = $this->em->getRepository(User::class)->findOneBy([], ['id' => 'asc']);
        $assistanceBeneficiary = $this->assistanceBeneficiaryWithoutRelief();
        $bnf = $assistanceBeneficiary->getBeneficiary();

        $reliefPackage = $this->createReliefPackage($assistanceBeneficiary);

        $smartcardBeneficiary = $this->getSmartcardForBeneficiary('1234ABC', $bnf);
        $smartcardBeneficiary->setState(SmartcardStates::INACTIVE);
        $date = new DateTime('now');
        $smartcardBeneficiary->addDeposit(
            $this->depositFactory->create(
                $smartcardBeneficiary->getSerialNumber(),
                DepositInputType::create($reliefPackage->getId(), 100, null, $date),
                $depositor
            )
        );

        $this->em->persist($smartcardBeneficiary);
        $this->em->flush();

        $headers = [
            'HTTP_COUNTRY' => 'KHM',
            'PHP_AUTH_USER' => 'admin@example.org',
            'PHP_AUTH_PW' => 'pin1234',
            'CONTENT_TYPE' => 'application/json',
        ];
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
        ], JSON_THROW_ON_ERROR);

        $this->client->request(
            'POST',
            '/api/basic/vendor-app/v4/smartcards/' . $smartcardBeneficiary->getSerialNumber() . '/purchase',
            [],
            [],
            $headers,
            $content
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
    }

    public function testPurchaseShouldBeAllowedForNonexistentSmartcardV4()
    {
        /** @var Assistance|null $assistance */
        $assistance = $this->em->getRepository(Assistance::class)->findOneBy([], ['id' => 'asc']);
        if (!$assistance) {
            $this->markTestSkipped('There is no assistance in database.');
        }

        $nonexistentSmarcard = '23456789012';
        $bnf = $this->em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'asc']);

        $headers = [
            'HTTP_COUNTRY' => 'KHM',
            'PHP_AUTH_USER' => 'admin@example.org',
            'PHP_AUTH_PW' => 'pin1234',
            'CONTENT_TYPE' => 'application/json',
        ];
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
        ], JSON_THROW_ON_ERROR);

        $this->client->request(
            'POST',
            '/api/basic/vendor-app/v4/smartcards/' . $nonexistentSmarcard . '/purchase',
            [],
            [],
            $headers,
            $content
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        /** @var SmartcardBeneficiary $smartcardBeneficiary */
        $smartcardBeneficiary = $this->em->getRepository(SmartcardBeneficiary::class)->findBySerialNumberAndBeneficiary(
            $nonexistentSmarcard,
            $bnf
        );

        $this->assertNotNull($smartcardBeneficiary, 'Smartcard must be registered to system');
        $this->assertTrue($smartcardBeneficiary->isSuspicious(), 'Smartcard registered by purchase must be suspected');
    }

    public function testChangeStateToInactive()
    {
        $smartcardBeneficiaryRepository = self::getContainer()->get(SmartcardBeneficiaryRepository::class);
        $bnf = $this->em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'asc']);
        $smartcardBeneficiary = $this->getSmartcardForBeneficiary('1234ABC', $bnf);

        $this->request('PATCH', '/api/basic/offline-app/v1/smartcards/' . $smartcardBeneficiary->getSerialNumber(), [
            'state' => SmartcardStates::INACTIVE,
            'createdAt' => '2020-02-02T12:00:00Z',
            'beneficiaryId' => $bnf->getId(),
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertEquals(\Symfony\Component\HttpFoundation\Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $smartcardBeneficiary = $smartcardBeneficiaryRepository->find($smartcardBeneficiary->getId());
        $this->assertEquals(SmartcardStates::INACTIVE, $smartcardBeneficiary->getState());
    }

    public function testChangeStateToInactiveDoubled(): void
    {
        $smartcardService = self::getContainer()->get(SmartcardService::class);
        $bnf = $this->em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'asc']);
        $smartcardBeneficiary = $this->getSmartcardForBeneficiary('1234ABC', $bnf);
        $date = '2005-02-02T12:00:00Z';
        $changeInputType = ChangeSmartcardInputType::create(SmartcardStates::INACTIVE, DateTime::createFromFormat('Y-m-d\TH:i:sO', $date));
        $smartcardService->change($smartcardBeneficiary, $changeInputType);

        $this->request('PATCH', '/api/basic/offline-app/v1/smartcards/' . $smartcardBeneficiary->getSerialNumber(), [
            'state' => SmartcardStates::INACTIVE,
            'createdAt' => $date,
            'beneficiaryId' => $bnf->getId(),
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertEquals(\Symfony\Component\HttpFoundation\Response::HTTP_ACCEPTED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * This is test of issue #PIN-1572: Synces are in wrong order.
     * Vendor performs sync purchases before Field officer performs sync smartcard deposit. In result, currency is missing.
     */
    public function testPurchasesShouldHaveCurrencyInNotPresentInRequestStep1()
    {
        $nonexistentSmarcard = '123ABCDE';

        foreach (
            $this->em->getRepository(SmartcardBeneficiary::class)->findBy(
                ['serialNumber' => $nonexistentSmarcard],
                ['id' => 'asc']
            ) as $smartcardBeneficiary
        ) {
            $this->em->remove($smartcardBeneficiary);
        }
        $this->em->flush();

        $modalityType = ModalityType::SMART_CARD;
        /** @var Commodity $commodity */
        $commodity = $this->em->getRepository(Commodity::class)->findBy(
            ['modalityType' => $modalityType],
            ['id' => 'asc']
        )[0];
        $assistance = $commodity->getAssistance();
        $beneficiary = $assistance->getDistributionBeneficiaries()[0]->getBeneficiary();
        $assistance = $assistance->getDistributionBeneficiaries()[0]->getAssistance();

        $headers = [
            'HTTP_COUNTRY' => 'KHM',
            'PHP_AUTH_USER' => 'admin@example.org',
            'PHP_AUTH_PW' => 'pin1234',
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request(
            'POST',
            '/api/basic/vendor-app/v4/smartcards/' . $nonexistentSmarcard . '/purchase',
            [],
            [],
            $headers,
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
            ], JSON_THROW_ON_ERROR)
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $smartcardBeneficiary = $this->em->getRepository(SmartcardBeneficiary::class)->findBySerialNumberAndBeneficiary(
            $nonexistentSmarcard,
            $beneficiary
        );
        $this->assertNotNull($smartcardBeneficiary, "Smartcard missing");
        $value = 0;
        foreach ($smartcardBeneficiary->getPurchases() as $purchase) {
            foreach ($purchase->getRecords() as $record) {
                $value += $record->getValue();
            }
        }
        $this->assertCount(1, $smartcardBeneficiary->getPurchases());
        $this->assertEquals(200, $value);

        return [$nonexistentSmarcard, $assistance, $beneficiary];
    }

    /**
     * @depends testPurchasesShouldHaveCurrencyInNotPresentInRequestStep1
     */
    public function testPurchasesShouldHaveCurrencyInNotPresentInRequestStep2($array)
    {
        [$smartcardBeneficiary, $assistance, $beneficiary] = $array;

        $this->request('POST', '/api/basic/offline-app/v1/smartcards', [
            'serialNumber' => $smartcardBeneficiary,
            'beneficiaryId' => $beneficiary->getId(),
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        return [$smartcardBeneficiary, $assistance, $beneficiary];
    }

    /**
     * @depends testPurchasesShouldHaveCurrencyInNotPresentInRequestStep2
     */
    public function testPurchasesShouldHaveCurrencyInNotPresentInRequestStep3($array): void
    {
        $this->markTestSkipped('This should be re-factorized to current deposit endpoint.');

        [$nonexistentSmarcard, $distribution, $beneficiary] = $array;

        $assistanceBeneficiary = $this->em->getRepository(AssistanceBeneficiary::class)->findOneBy([
            'assistance' => $distribution,
            'beneficiary' => $beneficiary,
        ], ['id' => 'asc']);

        $reliefPackage = $this->createReliefPackage($assistanceBeneficiary);

        $this->request('PATCH', '/api/basic/offline-app/v3/smartcards/' . $nonexistentSmarcard . '/deposit', [
            'value' => 500,
            'createdAt' => '2020-02-02T12:00:00+0001',
            'beneficiaryId' => $reliefPackage->getAssistanceBeneficiary()->getBeneficiary()->getId(),
            'distributionId' => $reliefPackage->getAssistanceBeneficiary()->getAssistance()->getId(),
        ]);

        /** @var SmartcardBeneficiary $smartcardBeneficiary */
        $smartcardBeneficiary = $this->em->getRepository(SmartcardBeneficiary::class)->findBySerialNumberAndBeneficiary(
            $nonexistentSmarcard,
            $beneficiary
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertNotNull($smartcardBeneficiary);
        $this->assertNotNull($smartcardBeneficiary->getCurrency());
        $this->assertNotNull($smartcardBeneficiary->getPurchases()[0]->getCurrency());
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

        $smartcardBeneficiary = $this->getSmartcardForBeneficiary('1234ABC', $bnf);
        $date = new DateTime('now');
        $smartcardBeneficiary->addDeposit(
            $this->depositFactory->create(
                $smartcardBeneficiary->getSerialNumber(),
                DepositInputType::create($reliefPackage->getId(), 600, null, $date),
                $depositor
            )
        );

        $this->em->persist($smartcardBeneficiary);
        $this->em->flush();

        $headers = [
            'HTTP_COUNTRY' => 'KHM',
            'PHP_AUTH_USER' => 'admin@example.org',
            'PHP_AUTH_PW' => 'pin1234',
            'CONTENT_TYPE' => 'application/json',
        ];
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
        $this->client->request(
            'POST',
            '/api/basic/vendor-app/v4/smartcards/' . $smartcardBeneficiary->getSerialNumber() . '/purchase',
            [],
            [],
            $headers,
            json_encode($requestBody, JSON_THROW_ON_ERROR)
        );
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $content = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('value', $content);
        $smartCardPurchasesCount1 = count($this->em->getRepository(SmartcardPurchase::class)->findAll());

        $this->setUp();

        //second request with the same bnfId+vendorId+createdAt
        $this->client->request(
            'POST',
            '/api/basic/vendor-app/v4/smartcards/' . $smartcardBeneficiary->getSerialNumber() . '/purchase',
            [],
            [],
            $headers,
            json_encode($requestBody, JSON_THROW_ON_ERROR)
        );
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $cnt = $this->client->getResponse()->getContent();
        $content = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('value', $content);
        $smartCardPurchasesCount2 = count($this->em->getRepository(SmartcardPurchase::class)->findAll());
        $this->assertEquals($smartCardPurchasesCount1, $smartCardPurchasesCount2);

        $this->setUp();

        //third request with same bnfId+vendorId+ createdAt+1second
        $requestBody['createdAt'] = '2020-02-02T11:00:01Z';
        $this->client->request(
            'POST',
            '/api/basic/vendor-app/v4/smartcards/' . $smartcardBeneficiary->getSerialNumber() . '/purchase',
            [],
            [],
            $headers,
            json_encode($requestBody, JSON_THROW_ON_ERROR)
        );
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $content = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('value', $content);
        $smartCardPurchasesCount3 = count($this->em->getRepository(SmartcardPurchase::class)->findAll());
        $this->assertGreaterThan($smartCardPurchasesCount1, $smartCardPurchasesCount3);

        $this->setUp();

        //fourth request with same bnfId+vendorId+ createdAt+2seconds
        $requestBody['createdAt'] = '2020-02-02T11:00:02Z';
        $this->client->request(
            'POST',
            '/api/basic/vendor-app/v4/smartcards/' . $smartcardBeneficiary->getSerialNumber() . '/purchase',
            [],
            [],
            $headers,
            json_encode($requestBody, JSON_THROW_ON_ERROR)
        );
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $content = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('value', $content);
        $smartCardPurchasesCount4 = count($this->em->getRepository(SmartcardPurchase::class)->findAll());
        $this->assertGreaterThan($smartCardPurchasesCount3, $smartCardPurchasesCount4);
        $this->assertGreaterThan($smartCardPurchasesCount1, $smartCardPurchasesCount4);

        $this->setUp();

        //fifth request with same bnfId+vendorId+ createdAt+2seconds
        $this->client->request(
            'POST',
            '/api/basic/vendor-app/v4/smartcards/' . $smartcardBeneficiary->getSerialNumber() . '/purchase',
            [],
            [],
            $headers,
            json_encode($requestBody, JSON_THROW_ON_ERROR)
        );
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $content = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('value', $content);
        $smartCardPurchasesCount5 = count($this->em->getRepository(SmartcardPurchase::class)->findAll());
        $this->assertGreaterThan($smartCardPurchasesCount3, $smartCardPurchasesCount5);
        $this->assertEquals($smartCardPurchasesCount4, $smartCardPurchasesCount5);
    }

    private function someSmartcardAssistance(): ?Assistance
    {
        foreach ($this->em->getRepository(Assistance::class)->findAll() as $assistance) {
            foreach ($assistance->getCommodities() as $commodity) {
                if ($commodity->getModalityType() === ModalityType::SMART_CARD) {
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
                if (ModalityType::SMART_CARD !== $commodity->getModalityType()) {
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
        $assistanceBeneficiary->setBeneficiary(
            $this->em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'asc'])
        );

        $this->em->persist($assistanceBeneficiary);

        return $assistanceBeneficiary;
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
