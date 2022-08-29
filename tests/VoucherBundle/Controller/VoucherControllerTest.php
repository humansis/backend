<?php
namespace VoucherBundle\Tests\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Tests\BMSServiceTestCase;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\Product;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Entity\Voucher;

class VoucherControllerTest extends BMSServiceTestCase
{
    /** @var Booklet */
    protected $booklet;

    /**
     * @throws \Exception
     */
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("serializer");
        parent::setUpFunctionnal();
        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');

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
        $vendorName = 'Vendor from Syria';
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
        $this->assertArrayHasKey('unassigned', $result);
        $this->assertArrayHasKey('inconsistent', $result);
        $this->assertArrayHasKey('valid', $result);

        $this->assertCount(1, $result['not_exists']);
        $this->assertCount(0, $result['redeemed']);
        $this->assertCount(0, $result['unused']);
        $this->assertCount(0, $result['unassigned']);
        $this->assertCount(0, $result['inconsistent']);
        $this->assertCount(0, $result['valid']);
    }

    public function testValidBatchRedemption(): array
    {
        $this->markTestSkipped('Voucher are suspended');
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $vendor = $this->em->getRepository(Vendor::class)->findOneBy([], ['id'=>'asc']);
        $vendorId = $vendor->getId();
        $usedButUnredeemedByVendor = $this->em->getRepository(Voucher::class)->findUsedButUnredeemedByVendor($vendor);
        $batchToRedeem = [
            "vouchers" => array_map(function (Voucher $voucher) { return $voucher->getId(); }, $usedButUnredeemedByVendor),
        ];

        $crawler = $this->request('POST', '/api/wsse/vouchers/purchases/redeem-batch/' . $vendorId, $batchToRedeem);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($result);

        $this->assertArrayHasKey('datetime', $result);
        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('vendor', $result);
        $this->assertArrayHasKey('redeemedAt', $result);
        $this->assertArrayHasKey('redeemedBy', $result);
        $this->assertArrayHasKey('voucherIds', $result);

        $this->assertIsArray($result['voucherIds']);

        return $result;
    }

    /**
     * @depends testValidBatchRedemption
     *
     * @param array $newBatchRedemption
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testGetRedeemedBatches(array $newBatchRedemption): void
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $vendorId = $newBatchRedemption['vendor'];

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

    /**
     * @depends testValidBatchRedemption
     *
     * @param array $newBatchRedemption
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testGetVoucherRedemptionBatch(array $newBatchRedemption)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $id = $newBatchRedemption['id'];

        $this->request('GET', '/api/wsse/vouchers/purchases/redemption-batch/'.$id);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($result);

        $this->assertArrayHasKey('datetime', $result);
        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('vendor', $result);
        $this->assertArrayHasKey('redeemedAt', $result);
        $this->assertArrayHasKey('redeemedBy', $result);
        $this->assertArrayHasKey('voucherIds', $result);

        $this->assertIsArray($result['voucherIds']);
    }

    /**
     * @depends testValidBatchRedemption
     *
     * @param array $newBatchRedemption
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testRedeemedVoucherReturnsRedeemedAt(array $newBatchRedemption)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $voucherId = current($newBatchRedemption['voucherIds']);

        $this->request('GET', '/api/wsse/vouchers/'.$voucherId);
        $voucher = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertNotNull($voucher['redeemed_at'], 'Redeemed voucher with id '.$voucherId.' should have redeemedAt not null');
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

        $this->assertArrayHasKey('not_exists', $result);
        $this->assertArrayHasKey('redeemed', $result);
        $this->assertArrayHasKey('unused', $result);
        $this->assertArrayHasKey('unassigned', $result);
        $this->assertArrayHasKey('inconsistent', $result);
        $this->assertArrayHasKey('valid', $result);

        $this->assertCount(1, $result['not_exists']);
        $this->assertCount(0, $result['redeemed']);
        $this->assertCount(0, $result['unused']);
        $this->assertCount(0, $result['unassigned']);
        $this->assertCount(0, $result['inconsistent']);
        $this->assertCount(0, $result['valid']);
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
