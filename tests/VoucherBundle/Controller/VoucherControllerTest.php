<?php
namespace VoucherBundle\Tests\Controller;

use Tests\BMSServiceTestCase;
use UserBundle\Entity\User;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Entity\Voucher;

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
        $this->setDefaultSerializerName("jms_serializer");
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

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);
    }

    /**
     * @dataProvider getValidVoucherData
     * @throws \Exception
     */
    public function testSuccessfullyCreateVoucher($voucherData)
    {
        $voucherData['booklet'] = $this->booklet;
        $voucherData['bookletCode'] = $this->booklet->getCode();
        // Second step
        // Create the vendor with the email and the salted password. The user should be enable
        $crawler = $this->request('PUT', '/api/wsse/vouchers', $voucherData);
        $voucher = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed. " . $this->client->getResponse()->getContent());
        $this->assertArrayHasKey('id', $voucher);
        $this->assertArrayHasKey('booklet', $voucher);
        return $voucher;
    }

    /**
     * @depends testSuccessfullyCreateVoucher
     * @param $newVoucher
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    /* FIXME: was broken by refaktoring scann method
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
        $this->assertTrue($voucherSearch->getUsedAt() !== null);

        return $newVoucherReceived;
    }
    */

    public function testRedeemVoucher() : void
    {
        $booklet = $this->em->getRepository(Booklet::class)->findOneBy([]);
        $user = $this->em->getRepository(User::class)->findOneBy([]);
        $voucher = new Voucher(uniqid(), 1000, $booklet);
        $voucher->distribute($user, new \DateTime());
        $voucher->use($user, new \DateTime());
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
        $this->assertTrue($voucherSearch->getUsedAt() !== null, "Voucher has no used date");
        $this->assertTrue($voucherSearch->getRedeemedAt() !== null, "Voucher has no redeem date");
        $this->assertEquals(Voucher::STATE_REDEEMED, $voucherSearch->getStatus(), "Voucher isn't in redeem state");
    }

    public function testRedeemInvalidVoucher() : void
    {
        $booklet = $this->em->getRepository(Booklet::class)->findOneBy([]);
        $user = $this->em->getRepository(User::class)->findOneBy([]);
        $voucher = new Voucher(uniqid(), 1000, $booklet);
        $voucher->distribute($user, new \DateTime());
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

    public function getValidVoucherData() : array
    {
        return [
            "standard" => [[
                'number_vouchers' => 3,
                'currency' => 'USD',
                'values' => [1, 2, 3],
            ]],
            "reversed order" => [[
                'number_vouchers' => 3,
                'currency' => 'USD',
                'values' => [3, 2, 1],
            ]],
            "less values than count" => [[
                'number_vouchers' => 3,
                'currency' => 'USD',
                'values' => [1],
            ]],
        ];
    }

    /**
     * @dataProvider getInvalidVoucherData
     * @throws \Exception
     */
    public function testFailedCreateVoucher($voucherData)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $voucherData['booklet'] = $this->booklet;
        $voucherData['bookletCode'] = $this->booklet->getCode();

        $crawler = $this->request('PUT', '/api/wsse/vouchers', $voucherData);
        $voucher = json_decode($this->client->getResponse()->getContent(), true);

        $response = $this->client->getResponse()->getContent();
        $this->assertTrue($this->client->getResponse()->isClientError(), "Wrong HTTP response. ".$this->client->getResponse()->getStatusCode(). " " . $response);
        $this->assertFalse($this->client->getResponse()->isServerError(), "Wrong HTTP response.". " " . $response);
        $this->assertFalse($this->client->getResponse()->isSuccessful(), "Request should fail." . $this->client->getResponse()->getContent(). " " . $response);
    }

    /**
     * @dataProvider getValidVoucherData
     * @throws \Exception
     */
    public function testFailedCreateVoucherMissingBooklet($voucherData)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $voucherData['bookletCode'] = $this->booklet->getCode();

        $crawler = $this->request('PUT', '/api/wsse/vouchers', $voucherData);
        $voucher = json_decode($this->client->getResponse()->getContent(), true);

        $response = $this->client->getResponse()->getContent();
        $this->assertTrue($this->client->getResponse()->isClientError(), "Wrong HTTP response. ".$this->client->getResponse()->getStatusCode(). " " . $response);
        $this->assertFalse($this->client->getResponse()->isServerError(), "Wrong HTTP response.". " " . $response);
        $this->assertFalse($this->client->getResponse()->isSuccessful(), "Request should fail." . $this->client->getResponse()->getContent(). " " . $response);
    }

    /**
     * @dataProvider getValidVoucherData
     * @throws \Exception
     */
    public function testFailedCreateVoucherMissingBookletCode($voucherData)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $voucherData['booklet'] = $this->booklet;

        $crawler = $this->request('PUT', '/api/wsse/vouchers', $voucherData);
        $voucher = json_decode($this->client->getResponse()->getContent(), true);

        $response = $this->client->getResponse()->getContent();
        $this->assertTrue($this->client->getResponse()->isClientError(), "Wrong HTTP response. ".$this->client->getResponse()->getStatusCode(). " " . $response);
        $this->assertFalse($this->client->getResponse()->isServerError(), "Wrong HTTP response.". " " . $response);
        $this->assertFalse($this->client->getResponse()->isSuccessful(), "Request should fail." . $this->client->getResponse()->getContent(). " " . $response);
    }

    public function getInvalidVoucherData() : array
    {
        return [
            "zero count" => [[
                'number_vouchers' => 0,
                'currency' => 'USD',
                'values' => [1, 2, 3],
            ]],
            "blank" => [[
                'number_vouchers' => 0,
                'currency' => 'USD',
                'values' => [],
            ]],
//            "standard" => [[
//                'number_vouchers' => 3,
//                'currency' => 'USD',
//                'values' => [1, 2, 3],
//            ]],
            "currency missing" => [[
                'number_vouchers' => 3,
                'values' => [1, 2, 3],
            ]],
            "float count" => [[
                'number_vouchers' => 0.5,
                'currency' => 'USD',
                'values' => [1, 2, 3],
            ]],
            "negative count" => [[
                'number_vouchers' => -3,
                'currency' => 'USD',
                'values' => [1, 2, 3],
            ]],
            "currency rubbish" => [[
                'number_vouchers' => 3,
                'currency' => 'AAACCCDDD',
                'values' => [1, 2, 3],
            ]],
            "values missing" => [[
                'number_vouchers' => 3,
                'currency' => 'USD',
            ]],
            "values are strings" => [[
                'number_vouchers' => 3,
                'currency' => 'USD',
                'values' => ["a", "b", "c"],
            ]],
            "count missing" => [[
                'currency' => 'USD',
                'values' => [1, 2, 3],
            ]],
            "empty" => [[
            ]],
            "different currency code than booklet" => [[
                'number_vouchers' => 3,
                'currency' => 'CZK',
                'values' => [1, 2, 3],
            ]],
            "empty values" => [[
                'number_vouchers' => 3,
                'currency' => 'USD',
                'values' => [],
            ]],
        ];
    }
}
