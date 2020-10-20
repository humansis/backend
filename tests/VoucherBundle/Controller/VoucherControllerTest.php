<?php
namespace VoucherBundle\Tests\Controller;

use Tests\BMSServiceTestCase;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\Product;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Entity\Voucher;
use VoucherBundle\Entity\VoucherPurchase;

class VoucherControllerTest extends BMSServiceTestCase
{
    /** @var Booklet */
    protected $booklet;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("serializer");
        parent::setUpFunctionnal();
        // Get a Client instance for simulate a browser
        $this->client = $this->container->get('test.client');

        // We create a new booklet if we have not already created one
        $randomBookletCode = join('-', [rand(0, 999), rand(0, 999), rand(0, 999)]);
        $this->booklet = new Booklet();
        $this->booklet->setCode($randomBookletCode)
            ->setNumberVouchers(0)
            ->setStatus(0)
            ->setCountryISO3('KHM')
            ->setCurrency('USD');

        $this->em->persist($this->booklet);
        $this->em->flush();
    }

    /**
     * @throws \Exception
     */
    public function testCreateVoucher()
    {
        $body = [
            'number_vouchers' => 3,
            'bookletCode' => $this->booklet->getCode(),
            'currency' => 'USD',
            'booklet' => $this->booklet,
            'values' => [1, 2, 3],
        ];

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the vendor with the email and the salted password. The user should be enable
        $crawler = $this->request('PUT', '/api/wsse/vouchers', $body);
        $voucher = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('id', $voucher);
        $this->assertArrayHasKey('booklet', $voucher);
        return $voucher;
    }

    /**
     * @throws \Exception
     */
    public function testGetAllVouchers()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/vouchers');
        $vouchers = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        if (!empty($vouchers)) {
            $voucher = $vouchers[0];

            $this->assertArrayHasKey('code', $voucher);
            $this->assertArrayHasKey('booklet', $voucher);
            $this->assertArrayHasKey('id', $voucher);
        } else {
            $this->markTestIncomplete("You currently don't have any vouchers in your database.");
        }

        return $vouchers;
    }


    /**
     * @depends testCreateVoucher
     * @param $newVoucher
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetVoucher($newVoucher)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);


        $crawler = $this->request('GET', '/api/wsse/vouchers/' . $newVoucher['id']);
        $voucher = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('id', $voucher);
        $this->assertArrayHasKey('code', $voucher);
        $this->assertArrayHasKey('booklet', $voucher);

        return $voucher;
    }

    /**
     * @depends testCreateVoucher
     * @param $newVoucher
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testUseVoucher($newVoucher)
    {
        $vendorName = 'vendor';
        $vendor = $this->em->getRepository(Vendor::class)->findOneByName($vendorName);
        if (null === $vendor) {
            $this->markTestIncomplete("Expected vendor user account '$vendorName' missing.");
        }
        $vendorId = $vendor->getId();
        // TODO: Check date format
        $body = [
            [
                "id" => $newVoucher['id'],
                "vendorId" => $vendorId,
                "productIds" => [1, 2],
                "used_at" => "2019-02-20 09:00:00"
            ]
        ];

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Using a fake header or else a country is gonna be put in the body
        $crawler = $this->request('POST', '/api/wsse/vouchers/scanned', $body);
        $newVoucherReceived = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $voucherSearch = $this->em->getRepository(Voucher::class)->find($newVoucherReceived[0]['id']);
        $this->assertTrue($voucherSearch->getVoucherPurchase()->getCreatedAt() !== null);

        return $newVoucherReceived;
    }

    public function testRedeemVoucher() : void
    {
        $booklet = $this->em->getRepository(Booklet::class)->findOneBy([]);
        $vendor = $this->em->getRepository(Vendor::class)->findOneBy([]);
        $voucher = new Voucher(uniqid(), 1000, $booklet);
        $voucher->setVoucherPurchase(VoucherPurchase::create($vendor, new \DateTime('now')));
        $this->em->persist($voucher->getVoucherPurchase());
        $this->em->persist($voucher);
        $this->em->flush();

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body = [
            'id' => $voucher->getId(),
        ];

        // Using a fake header or else a country is gonna be put in the body
        $crawler = $this->request('POST', '/api/wsse/vouchers/redeem', $body);
        $newVoucherReceived = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        /** @var Voucher $voucherSearch */
        $voucherSearch = $this->em->getRepository(Voucher::class)->find($newVoucherReceived['id']);
        $this->assertTrue($voucherSearch->getRedeemedAt() !== null, "Voucher has no redeem date");
    }

    public function testRedeemInvalidVoucher() : void
    {
        $booklet = $this->em->getRepository(Booklet::class)->findOneBy([]);
        $voucher = new Voucher(uniqid(), 1000, $booklet);
        $this->em->persist($voucher);
        $this->em->flush();

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body = [
            'id' => $voucher->getId(),
        ];

        // Using a fake header or else a country is gonna be put in the body
        $crawler = $this->request('POST', '/api/wsse/vouchers/redeem', $body);
        $newVoucherReceived = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertFalse($this->client->getResponse()->isSuccessful(), "Request doesn't failed but it should: ".$this->client->getResponse()->getContent());
        $this->assertFalse($this->client->getResponse()->isServerError(), "Request should fail but it ends with server error: ".$this->client->getResponse()->getContent());
        $this->assertTrue($this->client->getResponse()->isClientError(), "Request should fail with client error: ".$this->client->getResponse()->getContent());
    }

    public function testGetRedeemedBatches(): void
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $voucher = $this->em->getRepository(Voucher::class)->findOneBy(['redeemedAt'=>null]);

        $vendorId = $this->em->getRepository(Vendor::class)->findOneBy([], ['id'=>'asc'])->getId();
        $purchase = new \VoucherBundle\InputType\VoucherPurchase();
        $purchase->setProducts([[
            'id' => 1,
            'quantity' => 5.9,
            'value' => 1000.05,
        ]]);
        $purchase->setVendorId($vendorId);
        $purchase->setVouchers([$voucher]);
        $purchase->setCreatedAt(new \DateTime());
        $purchaseService = $this->container->get('voucher.purchase_service');
        $p1 = $purchaseService->purchase($purchase);
        $voucher->redeem();
        $this->em->persist($p1);
        $this->em->persist($p1);
        $this->em->flush();

        $crawler = $this->request('GET', '/api/wsse/vouchers/purchases/redeemed-batches/' . $vendorId);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $batches = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($batches);
        foreach ($batches as $batch) {
            $this->assertIsArray($batch);
            $this->assertArrayHasKey('date', $batch);
            $this->assertArrayHasKey('count', $batch);
            $this->assertArrayHasKey('value', $batch);

            $this->assertRegExp('/\d\d-\d\d-\d\d\d\d \d\d:\d\d/', $batch['date'], "Wrong datetime format");
            $this->assertIsNumeric($batch['count']);
            $this->assertIsNumeric($batch['value']);
        }
    }

    public function testCheckBatchRedemption(): void
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $vendor = $this->em->getRepository(Vendor::class)->findOneBy([], ['id'=>'asc']);
        $vendorId = $vendor->getId();

        $batchToRedeemCheck = [
            "vouchers" => [-1],
        ];

        $crawler = $this->request('POST', '/api/wsse/vouchers/purchases/redeem-check/' . $vendorId, $batchToRedeemCheck);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('not_exists', $result);
        $this->assertArrayHasKey('redeemed', $result);
        $this->assertArrayHasKey('unused', $result);
        $this->assertArrayHasKey('valid', $result);

        $this->assertCount(1, $result['not_exists']);
        $this->assertCount(0, $result['redeemed']);
        $this->assertCount(0, $result['unused']);
        $this->assertCount(0, $result['valid']);
    }

    public function testValidBatchRedemption(): void
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $vendor = $this->em->getRepository(Vendor::class)->findOneBy([], ['id'=>'asc']);
        $vendorId = $vendor->getId();
        $vouchers = $this->em->getRepository(Voucher::class)->findBy([
            'redeemedAt' => null,
        ]);
        $purchaseService = $this->container->get('voucher.purchase_service');
        $anyProduct = $this->em->getRepository(Product::class)->findOneBy([], ['id'=>'asc']);
        foreach ($vouchers as $voucher) {
            $purchaseInput = new \VoucherBundle\InputType\VoucherPurchase();
            $purchaseInput->setCreatedAt(new \DateTime());
            $purchaseInput->setVouchers([$voucher]);
            $purchaseInput->setVendorId($vendorId);
            $purchaseInput->setProducts([[
                'id' => $anyProduct->getId(),
                'quantity' => 5.9,
                'value' => 1000.05,
            ]]);
            $purchaseService->purchase($purchaseInput);
        }
        $batchToRedeem = [
            "vouchers" => array_map(function (Voucher $voucher) { return $voucher->getId(); }, $vouchers),
        ];

        $crawler = $this->request('POST', '/api/wsse/vouchers/purchases/redeem-batch/' . $vendorId, $batchToRedeem);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($result);
    }

    public function testInvalidBatchRedemption(): void
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $anyVendor = $this->em->getRepository(Vendor::class)->findOneBy([], ['id'=>'asc']);
        $vendorId = $anyVendor->getId();
        $batchToRedeem = [
            "vouchers" => [-1],
        ];

        $crawler = $this->request('POST', '/api/wsse/vouchers/purchases/redeem-batch/' . $vendorId, $batchToRedeem);
        $content = $this->client->getResponse()->getContent();
        $this->assertFalse($this->client->getResponse()->isSuccessful(), "Request shouldnt be successful: " . $content);
        $this->assertTrue($this->client->getResponse()->isClientError(), "Request should end with client error: " . $content);
        $this->assertFalse($this->client->getResponse()->isServerError(), "Request failed: " . $content);
        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
    }

    /**
     * @depends testGetVoucher
     *
     * @param $voucherToDelete
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testDeleteFromDatabase($voucherToDelete)
    {
        // Get the previous voucher because the last one was set as used so deletion won't work
        $voucherToDelete['id']--;

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);
        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('DELETE', '/api/wsse/vouchers/' . $voucherToDelete['id']);
        $success = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $this->assertTrue($success);
    }


    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testDeleteBatchVouchers()
    {
        $bookletId = $this->booklet->getId();
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('DELETE', '/api/wsse/vouchers/delete_batch/' . $bookletId);
        $success = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $this->assertTrue($success);

        $this->em->remove($this->booklet);
        $this->em->flush();
    }
}
