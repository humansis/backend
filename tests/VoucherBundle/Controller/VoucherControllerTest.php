<?php
namespace VoucherBundle\Tests\Controller;

use Tests\BMSServiceTestCase;
use VoucherBundle\Entity\Voucher;

class VoucherControllerTest extends BMSServiceTestCase
{
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
    }

    /**
     * @throws \Exception
     */
    public function testCreateVoucher()
    {
        $body = [
            'used' => false,
            'numberVouchers' => 5,
            'bookletCode' => 'leMhk#145-147-145',
            'currency' => 'USD',
            'bookletID' => 146, 
            'value' => 10,
        ];

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the vendor with the email and the salted password. The user should be enable
        $crawler = $this->request('PUT', '/api/wsse/new_voucher', $body);
        $voucher = json_decode($this->client->getResponse()->getContent(), true);
        var_dump($voucher);
        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        // $this->assertArrayHasKey('username', $booklet);
        // $this->assertArrayHasKey('shop', $booklet);
        return $voucher;
    }
}