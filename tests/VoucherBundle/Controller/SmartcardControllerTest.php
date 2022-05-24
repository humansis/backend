<?php

namespace VoucherBundle\Tests\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\DataFixtures\VendorFixtures;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Enum\ModalityType;
use NewApiBundle\Repository\Smartcard\PreliminaryInvoiceRepository;
use Tests\BMSServiceTestCase;
use UserBundle\Entity\User;
use VoucherBundle\DTO\PreliminaryInvoice;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardDeposit;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\Invoice;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Enum\SmartcardStates;
use VoucherBundle\InputType\SmartcardInvoice;
use VoucherBundle\Repository\SmartcardPurchaseRepository;

class SmartcardControllerTest extends BMSServiceTestCase
{
    public function setUp()
    {
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);
    }

    protected function tearDown()
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

        $smartcard = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('id', $smartcard);
        $this->assertArrayHasKey('serialNumber', $smartcard);
        $this->assertArrayHasKey('state', $smartcard);
        $this->assertArrayHasKey('currency', $smartcard);
        $this->assertEquals('1111111', $smartcard['serialNumber']);
        $this->assertEquals(SmartcardStates::ACTIVE, $smartcard['state']);
        $this->assertNull($smartcard['currency']);

        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumberAndBeneficiary('1111111', $bnf);
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

    public function testDepositToSmartcard()
    {
        $ab = $this->assistanceBeneficiaryWithoutRelief();
        $bnf = $ab->getBeneficiary();
        $smartcard = $this->getSmartcardForBeneficiary('1234ABC', $bnf);

        $reliefPackage = $this->createReliefPackage($ab);

        $this->request('PATCH', '/api/wsse/offline-app/v3/smartcards/'.$smartcard->getSerialNumber().'/deposit', [
            'value' => 255.25,
            'balance' => 260.00,
            'distributionId' => $reliefPackage->getAssistanceBeneficiary()->getAssistance()->getId(),
            'createdAt' => '2020-02-02T12:00:00Z',
            'beneficiaryId' => $bnf->getId(),
        ]);

        $smartcard = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('value', $smartcard);
        $this->assertArrayHasKey('currency', $smartcard);
        $this->assertEquals(255.25, $smartcard['value'], 0.0001);
        $this->assertNotNull($smartcard['currency']);
    }

    public function testDepositToInactiveSmartcard()
    {
        //TODO this test passes, but deposit to inactive smartcard is still possible. In request is mission distributionId

        $depositor = $this->em->getRepository(User::class)->findOneBy([], ['id' => 'asc']);
        /** @var AssistanceBeneficiary $assistanceBeneficiary */
        $assistanceBeneficiary = $this->assistanceBeneficiaryWithoutRelief();
        $bnf = $assistanceBeneficiary->getBeneficiary();

        /** @var Smartcard $smartcard */
        $smartcard = $this->getSmartcardForBeneficiary('1234ABC', $bnf);
        $smartcard->setState(SmartcardStates::INACTIVE);
        $smartcard->setBeneficiary($assistanceBeneficiary->getBeneficiary());

        $reliefPackage = $this->createReliefPackage($assistanceBeneficiary);

        $this->em->persist($reliefPackage);

        $deposit = SmartcardDeposit::create($smartcard, $depositor, $reliefPackage, 1000, null, new \DateTime('now'));
        $smartcard->addDeposit($deposit);

        $this->em->persist($smartcard);
        $this->em->flush();

        $this->request('PATCH', '/api/wsse/offline-app/v3/smartcards/'.$smartcard->getSerialNumber().'/deposit', [
            'value' => 500,
            'createdAt' => '2020-02-02T12:00:00+0200',
            'beneficiaryId' => $bnf->getId(),
        ]);

        $this->assertTrue($this->client->getResponse()->isClientError(), 'Request failed: '.$this->client->getResponse()->getContent());
    }

    public function testPurchase()
    {
        $depositor = $this->em->getRepository(User::class)->findOneBy([], ['id' => 'asc']);
        $assistanceBeneficiary = $this->assistanceBeneficiaryWithoutRelief();
        $bnf = $assistanceBeneficiary->getBeneficiary();

        $smartcard = $this->getSmartcardForBeneficiary('1234ABC', $bnf);

        $reliefPackage = $this->createReliefPackage($assistanceBeneficiary);

        $deposit = SmartcardDeposit::create($smartcard, $depositor, $reliefPackage, 600, null, new \DateTime('now'));
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
        ]);

        $this->client->request('PATCH', '/api/wsse/vendor-app/v3/smartcards/'.$smartcard->getSerialNumber().'/purchase', [], [], $headers, $content);

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

        $deposit = SmartcardDeposit::create($smartcard, $depositor, $reliefPackage, 600, null, new \DateTime('now'));
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

        $reliefPackage = $this->createReliefPackage($assistanceBeneficiary);

        $smartcard = $this->getSmartcardForBeneficiary('1234ABC', $bnf);
        $smartcard->setState(SmartcardStates::INACTIVE);
        $smartcard->addDeposit(SmartcardDeposit::create($smartcard, $depositor, $reliefPackage, 100, null, new \DateTime('now')));

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
        ]);

        $this->client->request('PATCH', '/api/wsse/vendor-app/v3/smartcards/'.$smartcard->getSerialNumber().'/purchase', [], [], $headers, $content);

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
        $smartcard->addDeposit(SmartcardDeposit::create($smartcard, $depositor, $reliefPackage, 100, null, new \DateTime('now')));

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

    public function testPurchaseShouldBeAllowedForNonexistentSmartcard()
    {
        $nonexistentSmarcard = '0123456789';

        $headers = ['HTTP_COUNTRY' => 'KHM'];
        $content = json_encode([
            'products' => [
                [
                    'id' => 1, // @todo replace for fixture
                    'value' => 400,
                    'quantity' => 1.2,
                ],
            ],
            'vendorId' => 1,
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $this->client->request('PATCH', '/api/wsse/vendor-app/v1/smartcards/'.$nonexistentSmarcard.'/purchase', [], [], $headers, $content);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());

        /** @var Smartcard $smartcard */
        $smartcard = $this->em->getRepository(Smartcard::class)->findOneBy(['serialNumber' => $nonexistentSmarcard]);

        $this->assertNotNull($smartcard, 'Smartcard must be registered to system');
        $this->assertTrue($smartcard->isSuspicious(), 'Smartcard registered by purchase must be suspected');
    }

    public function testPurchaseShouldBeAllowedForNonexistentSmartcardV2()
    {
        $nonexistentSmarcard = '1234567890';

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
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $this->client->request('PATCH', '/api/wsse/vendor-app/v2/smartcards/'.$nonexistentSmarcard.'/purchase', [], [], $headers, $content);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());

        /** @var Smartcard $smartcard */
        $smartcard = $this->em->getRepository(Smartcard::class)->findOneBy(['serialNumber' => $nonexistentSmarcard]);

        $this->assertNotNull($smartcard, 'Smartcard must be registered to system');
        $this->assertTrue($smartcard->isSuspicious(), 'Smartcard registered by purchase must be suspected');
    }

    public function testPurchaseShouldBeAllowedForNonexistentSmartcardV3()
    {
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
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $this->client->request('PATCH', '/api/wsse/vendor-app/v3/smartcards/'.$nonexistentSmarcard.'/purchase', [], [], $headers, $content);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());

        /** @var Smartcard $smartcard */
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumberAndBeneficiary($nonexistentSmarcard, $bnf);

        $this->assertNotNull($smartcard, 'Smartcard must be registered to system');
        $this->assertTrue($smartcard->isSuspicious(), 'Smartcard registered by purchase must be suspected');
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
        $bnf = $this->em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'asc']);
        $smartcard = $this->getSmartcardForBeneficiary('1234ABC', $bnf);

        $this->request('PATCH', '/api/wsse/offline-app/v1/smartcards/'.$smartcard->getSerialNumber(), [
            'state' => SmartcardStates::INACTIVE,
            'createdAt' => '2020-02-02T12:00:00Z',
            'beneficiaryId' => $bnf->getId(),
        ]);

        $smartcard = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('state', $smartcard);
        $this->assertEquals(SmartcardStates::INACTIVE, $smartcard['state']);
    }

    public function testGetPurchases(): void
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $vendor = $this->em->getRepository(Vendor::class)->findOneBy([], ['id' => 'asc']);
        $purchases = $this->em->getRepository(SmartcardPurchase::class)->findBy(['vendor' => $vendor], ['id' => 'asc']);
        $purchaseCount = count($purchases);

        $crawler = $this->request('GET', '/api/wsse/smartcards/purchases/'.$vendor->getId());
        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $summary = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('count', $summary);
        $this->assertArrayHasKey('value', $summary);

        $this->assertIsNumeric($summary['count']);
        $this->assertEquals($purchaseCount, $summary['count'], 'Wrong purchase count');
        $this->assertIsNumeric($summary['value']);
    }

    public function testGetUnredeemedPurchasesDetails(): void
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $vendor = $this->em->getRepository(Vendor::class)->findOneBy([], ['id' => 'asc']);

        $crawler = $this->request('GET', '/api/wsse/smartcards/purchases/'.$vendor->getId().'/details');
        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $details = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($details);
        foreach ($details as $detail) {
            $this->assertArrayHasKey('purchase_date', $detail);
            $this->assertArrayHasKey('purchase_amount', $detail);
            $this->assertArrayHasKey('beneficiary_id', $detail);
            $this->assertArrayHasKey('beneficiary_local_name', $detail);
            $this->assertArrayHasKey('beneficiary_en_name', $detail);

            $this->assertIsNumeric($detail['purchase_amount']);
            $this->assertIsNumeric($detail['purchase_amount']);
            $this->assertRegExp('/\d\d-\d\d-\d\d\d\d/', $detail['purchase_date']);
            $this->assertIsString($detail['beneficiary_local_name']);
            $this->assertIsString($detail['beneficiary_en_name']);
        }
    }

    public function testGetRedeemedBatches(): void
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $vendor = $this->em->getRepository(Vendor::class)->findOneBy(['name' => VendorFixtures::VENDOR_SYR_NAME], ['id' => 'asc']);
        $vendorId = $vendor->getId();
        /** @var Smartcard $smartcard */
        $smartcard = $this->em->getRepository(Smartcard::class)->findOneBy(['currency' => 'SYP', 'state'=>Smartcard::STATE_ACTIVE], ['id'=>'desc']);
        $smartcard->getDeposites()[0]->setDistributedAt(\DateTime::createFromFormat('Y-m-d', '2000-01-01'));
        $purchase = new \VoucherBundle\InputType\SmartcardPurchase();
        $purchase->setProducts([[
            'id' => 1,
            'quantity' => 5.9,
            'value' => 1000.05,
            'currency' => 'SYP',
        ]]);
        $purchase->setVendorId($vendorId);
        $purchase->setCreatedAt(\DateTime::createFromFormat('Y-m-d', '2000-01-02'));
        $purchaseService = self::$container->get('voucher.purchase_service');
        $smartcardService = self::$container->get('smartcard_service');
        $purchaseService->purchaseSmartcard($smartcard, $purchase);
        /** @var SmartcardPurchase $p2 */
        $p2 = $purchaseService->purchaseSmartcard($smartcard, $purchase);
        $purchase->setCreatedAt(\DateTime::createFromFormat('Y-m-d', '2000-01-03'));
        $p3 = $purchaseService->purchaseSmartcard($smartcard, $purchase);

        $redemptionBatch = new SmartcardInvoice();
        $redemptionBatch->setPurchases([$p2->getId(), $p3->getId()]);

        $smartcardService->redeem($vendor, $redemptionBatch, $user);

        $crawler = $this->request('GET', '/api/wsse/smartcards/batch?vendor='.$vendorId);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $batches = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($batches);
        foreach ($batches as $batch) {
            $this->assertIsArray($batch);
            $this->assertArrayHasKey('date', $batch);
            $this->assertArrayHasKey('count', $batch);
            $this->assertArrayHasKey('value', $batch);
            $this->assertArrayHasKey('currency', $batch);
            $this->assertArrayHasKey('project_id', $batch);
            $this->assertArrayHasKey('project_name', $batch);

            $this->assertRegExp('/\d\d-\d\d-\d\d\d\d \d\d:\d\d/', $batch['date'], 'Wrong datetime format');
            $this->assertIsNumeric($batch['count']);
            $this->assertIsNumeric($batch['value']);
            $this->assertRegExp('/\w\w\w/', $batch['currency'], 'Wrong currency format');
        }
    }

    public function testGetBatchPurchasesDetails(): void
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $vendor = $this->em->getRepository(Vendor::class)->findOneBy([], ['id' => 'asc']);
        $batch = $this->em->getRepository(Invoice::class)->findOneBy([
            'vendor' => $vendor,
        ], [
            'invoicedAt' => 'asc',
        ]);

        $crawler = $this->request('GET', '/api/wsse/smartcards/batch/'.$batch->getId().'/purchases');
        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $details = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($details);
        foreach ($details as $detail) {
            $this->assertArrayHasKey('purchase_datetime', $detail);
            $this->assertArrayHasKey('purchase_date', $detail);
            $this->assertArrayHasKey('purchase_amount', $detail);
            $this->assertArrayHasKey('beneficiary_id', $detail);
            $this->assertArrayHasKey('beneficiary_local_name', $detail);
            $this->assertArrayHasKey('beneficiary_en_name', $detail);

            $this->assertIsNumeric($detail['purchase_datetime']);
            $this->assertIsNumeric($detail['purchase_amount']);
            $this->assertRegExp('/\d\d-\d\d-\d\d\d\d/', $detail['purchase_date']);
            $this->assertIsString($detail['beneficiary_local_name']);
            $this->assertIsString($detail['beneficiary_en_name']);
        }
    }

    public function testGetBatchToRedemption(): void
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $vendorId = $this->em->getRepository(Vendor::class)->findOneBy([], ['id' => 'asc'])->getId();

        $crawler = $this->request('GET', '/api/wsse/smartcards/purchases/to-redemption/'.$vendorId);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $batchCandidates = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($batchCandidates);
        $batchCandidate = $batchCandidates[0];
        $this->assertIsArray($batchCandidate);
        $this->assertArrayHasKey('value', $batchCandidate);
        $this->assertArrayHasKey('purchases_ids', $batchCandidate);

        $this->assertIsNumeric($batchCandidate['value']);
        $this->assertIsArray($batchCandidate['purchases_ids']);
        foreach ($batchCandidate['purchases_ids'] as $id) {
            $this->assertIsInt($id);
        }
    }

    public function testConsistencyBatchToRedemptionWithSummary(): void
    {
        $this->markTestIncomplete('Old and without proper data preparation');
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $vendor = $this->em->getRepository(Vendor::class)->findOneBy([
            'name' => VendorFixtures::VENDOR_KHM_NAME,
        ], ['id' => 'asc']);
        $vendorId = $vendor->getId();

        $crawler = $this->request('GET', '/api/wsse/smartcards/purchases/to-redemption/'.$vendorId);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $batchCandidates = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($batchCandidates);
        $batchCandidate = $batchCandidates[0];
        $this->assertIsArray($batchCandidate);

        /** @var PreliminaryInvoiceRepository $repository */
        $repository = $this->em->getRepository(PreliminaryInvoice::class);
        $summary = $repository->findOneBy(['vendor'=>$vendor]);

        $this->assertCount(count($summary->getPurchasesIds()), $batchCandidate['purchases_ids'], 'There is wrong count number in batch to redeem');
        $this->assertEquals($summary->getValue(), $batchCandidate['value'], 'There is wrong value of batch to redeem');
    }

    /**
     * @depends testPurchaseV4
     */
    public function testBatchRedemption(): void
    {
        $this->markTestSkipped('Test ald endpoint');

        $vendor = $this->em->getRepository(Vendor::class)->findOneBy([
            'name' => VendorFixtures::VENDOR_SYR_NAME,
        ], ['id' => 'asc']);
        $repository = $this->em->getRepository(SmartcardPurchase::class);

        // TEST of test data, can be removed after clean fixtures
        /** @var SmartcardPurchase $purchase */
        foreach ($repository->findAll() as $purchase) {
            $currency = null;
            foreach ($purchase->getRecords() as $record) {
                if ($currency === null) $currency = $record->getCurrency();
                $this->assertEquals($currency, $record->getCurrency(), "Test data are broken");
            }
        }

        $preliminaryInvoices = $this->em->getRepository(\NewApiBundle\Entity\Smartcard\PreliminaryInvoice::class)
            ->findBy(['vendor'=>$vendor]);
        $this->assertIsArray($preliminaryInvoices);
        $this->assertGreaterThan(0, count($preliminaryInvoices), "Too little redemption preliminaryInvoices");
        /** @var PreliminaryInvoice $preliminaryInvoice */
        foreach ($preliminaryInvoices as $preliminaryInvoice) {
            $batchToInvoice = [
                'purchases' => $preliminaryInvoice->getPurchasesIds(),
            ];

            $this->setUp();

            $crawler = $this->request('POST', '/api/wsse/smartcards/purchases/redeem-batch/'.$vendor->getId(), $batchToInvoice);
            $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
            $result = json_decode($this->client->getResponse()->getContent(), true);
            $this->assertArrayHasKey('id', $result);
        }
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

        /** @var \DistributionBundle\Entity\ModalityType $modalityType */
        $modalityType = $this->em->getRepository(\DistributionBundle\Entity\ModalityType::class)->findOneBy(['name' => 'Smartcard'], ['id' => 'asc']);
        /** @var \DistributionBundle\Entity\Commodity $commodity */
        $commodity = $this->em->getRepository(\DistributionBundle\Entity\Commodity::class)->findBy(['modalityType' => $modalityType], ['id' => 'asc'])[0];
        $assistance = $commodity->getAssistance();
        $beneficiary = $assistance->getDistributionBeneficiaries()[0]->getBeneficiary();

        $this->client->request('PATCH', '/api/wsse/vendor-app/v3/smartcards/'.$nonexistentSmarcard.'/purchase', [], [], ['HTTP_COUNTRY' => 'KHM'],
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
        $assistanceBeneficiary = $this->someSmartcardAssistance()->getDistributionBeneficiaries()->get(0);
        /** @var Beneficiary $bnf */
        $bnf = $this->em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'asc']);

        $reliefPackage = $this->createReliefPackage($assistanceBeneficiary);

        $smartcard = $this->getSmartcardForBeneficiary('1234ABC', $bnf);
        $smartcard->addDeposit(SmartcardDeposit::create($smartcard, $depositor, $reliefPackage, 600, null, new \DateTime('now')));

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
        ];

        //first request
        $this->client->request('PATCH', '/api/wsse/vendor-app/v3/smartcards/'.$smartcard->getSerialNumber().'/purchase', [], [], $headers,
            json_encode($requestBody));
        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('value', $content);
        $smartCardPurchasesCount1 = count($this->em->getRepository(SmartcardPurchase::class)->findAll());

        $this->setUp();

        //second request with the same bnfId+vendorId+createdAt
        $this->client->request('PATCH', '/api/wsse/vendor-app/v3/smartcards/'.$smartcard->getSerialNumber().'/purchase', [], [], $headers,
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
        $this->client->request('PATCH', '/api/wsse/vendor-app/v3/smartcards/'.$smartcard->getSerialNumber().'/purchase', [], [], $headers,
            json_encode($requestBody));
        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('value', $content);
        $smartCardPurchasesCount3 = count($this->em->getRepository(SmartcardPurchase::class)->findAll());
        $this->assertGreaterThan($smartCardPurchasesCount1, $smartCardPurchasesCount3);

        $this->setUp();

        //fourth request with same bnfId+vendorId+ createdAt+2seconds
        $requestBody['createdAt'] = '2020-02-02T11:00:02Z';
        $this->client->request('PATCH', '/api/wsse/vendor-app/v3/smartcards/'.$smartcard->getSerialNumber().'/purchase', [], [], $headers,
            json_encode($requestBody));
        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('value', $content);
        $smartCardPurchasesCount4 = count($this->em->getRepository(SmartcardPurchase::class)->findAll());
        $this->assertGreaterThan($smartCardPurchasesCount3, $smartCardPurchasesCount4);
        $this->assertGreaterThan($smartCardPurchasesCount1, $smartCardPurchasesCount4);

        $this->setUp();

        //fifth request with same bnfId+vendorId+ createdAt+2seconds
        $this->client->request('PATCH', '/api/wsse/vendor-app/v3/smartcards/'.$smartcard->getSerialNumber().'/purchase', [], [], $headers,
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
