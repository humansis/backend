<?php

namespace VoucherBundle\Tests\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use DistributionBundle\Entity\Assistance;
use Tests\BMSServiceTestCase;
use UserBundle\Entity\User;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardDeposit;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\SmartcardRedemptionBatch;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Repository\SmartcardPurchaseRepository;

class SmartcardControllerTest extends BMSServiceTestCase
{
    public function setUp()
    {
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = $this->getContainer()->get('test.client');

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');
        if (!$smartcard) {
            $smartcard = new Smartcard('1234ABC', new \DateTime('now'));
            $smartcard->setBeneficiary($this->someSmartcardAssistance()->getDistributionBeneficiaries()->get(0)->getBeneficiary());
            $smartcard->setState(Smartcard::STATE_ACTIVE);
            $this->em->persist($smartcard);
            $this->em->flush();
        }
    }

    protected function tearDown()
    {
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');
        $this->em->remove($smartcard);
        $this->em->flush();

        parent::tearDown();
    }

    public function testRegisterSmartcard()
    {
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1111111');
        if ($smartcard) {
            $this->em->remove($smartcard);
            $this->em->flush();
        }

        $bnfId = $this->em->getRepository(Beneficiary::class)->findOneBy([])->getId();

        $this->request('POST', '/api/wsse/offline-app/v1/smartcards', [
            'serialNumber' => '1111111',
            'beneficiaryId' => $bnfId, // @todo replace for fixture
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $smartcard = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('id', $smartcard);
        $this->assertArrayHasKey('serialNumber', $smartcard);
        $this->assertArrayHasKey('state', $smartcard);
        $this->assertArrayHasKey('currency', $smartcard);
        $this->assertEquals('1111111', $smartcard['serialNumber']);
        $this->assertEquals(Smartcard::STATE_ACTIVE, $smartcard['state']);
        $this->assertNull($smartcard['currency']);

        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1111111');
        $this->em->remove($smartcard);
        $this->em->flush();
    }

    public function testRegisterDuplicateSmartcard()
    {
        $bnfId = $this->em->getRepository(Beneficiary::class)->findOneBy([])->getId();

        $this->request('POST', '/api/wsse/offline-app/v1/smartcards', [
            'serialNumber' => '1234ABC',
            'beneficiaryId' => $bnfId, // @todo replace for fixture
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request should failed: '.$this->client->getResponse()->getContent());
    }

    public function testDepositToSmartcard()
    {
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');

        $this->request('PATCH', '/api/wsse/offline-app/v1/smartcards/'.$smartcard->getSerialNumber().'/deposit', [
            'value' => 255.25,
            'distributionId' => $this->someSmartcardAssistance()->getId(),
            'createdAt' => '2020-02-02T12:00:00Z',
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
        $depositor = $this->em->getRepository(User::class)->findOneBy([]);
        $assistanceBeneficiary = $this->someSmartcardAssistance()->getDistributionBeneficiaries()->get(0);

        /** @var Smartcard $smartcard */
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');
        $smartcard->setState(Smartcard::STATE_INACTIVE);
        $smartcard->addDeposit(SmartcardDeposit::create($smartcard, $depositor, $assistanceBeneficiary, 1000, new \DateTime('now')));

        $this->em->persist($smartcard);
        $this->em->flush();

        $this->request('PATCH', '/api/wsse/offline-app/v1/smartcards/'.$smartcard->getSerialNumber().'/deposit', [
            'value' => 500,
            'createdAt' => '2020-02-02T12:00:00+0200',
        ]);

        $this->assertTrue($this->client->getResponse()->isClientError(), 'Request failed: '.$this->client->getResponse()->getContent());
    }

    public function testPurchase()
    {
        $depositor = $this->em->getRepository(User::class)->findOneBy([]);
        $assistanceBeneficiary = $this->someSmartcardAssistance()->getDistributionBeneficiaries()->get(0);

        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');
        $smartcard->addDeposit(SmartcardDeposit::create($smartcard, $depositor, $assistanceBeneficiary, 600, new \DateTime('now')));

        $this->em->persist($smartcard);
        $this->em->flush();

        $headers = ['HTTP_COUNTRY' => 'KHM'];
        $content = json_encode([
            'products' => [
                [
                    'id' => 1, // @todo replace for fixture
                    'value' => 300.25,
                    'quantity' => 1.2,
                ],
            ],
            'vendorId' => 1,
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $this->client->request('PATCH', '/api/wsse/vendor-app/v1/smartcards/'.$smartcard->getSerialNumber().'/purchase', [], [], $headers, $content);

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
        $depositor = $this->em->getRepository(User::class)->findOneBy([]);
        $assistanceBeneficiary = $this->someSmartcardAssistance()->getDistributionBeneficiaries()->get(0);

        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');
        $smartcard->setState(Smartcard::STATE_INACTIVE);
        $smartcard->addDeposit(SmartcardDeposit::create($smartcard, $depositor, $assistanceBeneficiary, 100, new \DateTime('now')));

        $this->em->persist($smartcard);
        $this->em->flush();

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

        $this->client->request('PATCH', '/api/wsse/vendor-app/v1/smartcards/'.$smartcard->getSerialNumber().'/purchase', [], [], $headers, $content);

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
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber($nonexistentSmarcard);

        $this->assertNotNull($smartcard, 'Smartcard must be registered to system');
        $this->assertTrue($smartcard->isSuspicious(), 'Smartcard registered by purchase must be suspected');
    }

    public function testChangeStateToInactive()
    {
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber('1234ABC');

        $this->request('PATCH', '/api/wsse/offline-app/v1/smartcards/'.$smartcard->getSerialNumber(), [
            'state' => Smartcard::STATE_INACTIVE,
            'createdAt' => '2020-02-02T12:00:00Z',
        ]);

        $smartcard = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('state', $smartcard);
        $this->assertEquals(Smartcard::STATE_INACTIVE, $smartcard['state']);
    }

    public function testGetPurchases(): void
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $vendor = $this->em->getRepository(Vendor::class)->findOneBy([], ['id' => 'asc']);
        $purchases = $this->em->getRepository(SmartcardPurchase::class)->findBy(['vendor' => $vendor]);
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

        $vendor = $this->em->getRepository(Vendor::class)->findOneBy([], ['id' => 'asc']);
        $vendorId = $vendor->getId();
        $smartcard = $this->em->getRepository(Smartcard::class)->findOneBy([]);
        $purchase = new \VoucherBundle\InputType\SmartcardPurchase();
        $purchase->setProducts([[
            'id' => 1,
            'quantity' => 5.9,
            'value' => 1000.05,
        ]]);
        $purchase->setVendorId($vendorId);
        $purchase->setCreatedAt(new \DateTime());
        $purchaseService = $this->getContainer()->get('voucher.purchase_service');
        $purchaseService->purchaseSmartcard($smartcard, $purchase);
        /** @var SmartcardPurchase $p2 */
        $p2 = $purchaseService->purchaseSmartcard($smartcard, $purchase);
        $p3 = $purchaseService->purchaseSmartcard($smartcard, $purchase);
        $redemptionBatch = new SmartcardRedemptionBatch(
            $vendor,
            new \DateTime(),
            $user,
            $this->em->getRepository(SmartcardPurchase::class)->countPurchasesValue([$p2, $p3]),
            [$p2, $p3]
        );
        $p2->setRedemptionBatch($redemptionBatch);
        $p3->setRedemptionBatch($redemptionBatch);
        $this->em->persist($p2);
        $this->em->persist($p3);
        $this->em->flush();

        $crawler = $this->request('GET', '/api/wsse/smartcards/batch?vendor='.$vendorId);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $batches = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($batches);
        foreach ($batches as $batch) {
            $this->assertIsArray($batch);
            $this->assertArrayHasKey('date', $batch);
            $this->assertArrayHasKey('count', $batch);
            $this->assertArrayHasKey('value', $batch);

            $this->assertRegExp('/\d\d-\d\d-\d\d\d\d \d\d:\d\d/', $batch['date'], 'Wrong datetime format');
            $this->assertIsNumeric($batch['count']);
            $this->assertIsNumeric($batch['value']);
        }
    }

    public function testGetBatchPurchasesDetails(): void
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $vendor = $this->em->getRepository(Vendor::class)->findOneBy([], ['id' => 'asc']);
        $batch = $this->em->getRepository(SmartcardRedemptionBatch::class)->findOneBy([
            'vendor' => $vendor,
        ], [
            'redeemedAt' => 'asc',
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
        $batch = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($batch);
        $this->assertArrayHasKey('value', $batch);
        $this->assertArrayHasKey('purchases_ids', $batch);

        $this->assertIsNumeric($batch['value']);
        $this->assertIsArray($batch['purchases_ids']);
        foreach ($batch['purchases_ids'] as $id) {
            $this->assertIsInt($id);
        }
    }

    public function testConsistencyBatchToRedemptionWithSummary(): void
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $vendor = $this->em->getRepository(Vendor::class)->findOneBy([], ['id' => 'asc']);
        $vendorId = $vendor->getId();

        $crawler = $this->request('GET', '/api/wsse/smartcards/purchases/to-redemption/'.$vendorId);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $batch = json_decode($this->client->getResponse()->getContent(), true);

        /** @var SmartcardPurchaseRepository $repository */
        $repository = $this->em->getRepository(SmartcardPurchase::class);
        $summary = $repository->countPurchasesToRedeem($vendor);

        $this->assertCount(count($summary->getPurchasesIds()), $batch['purchases_ids'], 'There is wrong count number in batch to redeem');
        $this->assertEquals($summary->getValue(), $batch['value'], 'There is wrong value of batch to redeem');
    }

    public function testBatchRedemption(): void
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $vendor = $this->em->getRepository(Vendor::class)->findOneBy([], ['id' => 'asc']);
        $vendorId = $vendor->getId();
        $purchases = $this->em->getRepository(\VoucherBundle\Entity\SmartcardPurchase::class)->findBy([
            'vendor' => $vendor,
            'redemptionBatch' => null,
        ]);
        $batchToRedeem = [
            'purchases' => array_map(function (\VoucherBundle\Entity\SmartcardPurchase $purchase) {
                return $purchase->getId();
            }, $purchases),
        ];

        $crawler = $this->request('POST', '/api/wsse/smartcards/purchases/redeem-batch/'.$vendorId, $batchToRedeem);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $result);
    }

    private function someSmartcardAssistance(): Assistance
    {
        foreach ($this->em->getRepository(Assistance::class)->findAll() as $assistance) {
            foreach ($assistance->getCommodities() as $commodity) {
                if ('Smartcard' === $commodity->getModalityType()->getName()) {
                    return $assistance;
                }
            }
        }
    }
}
