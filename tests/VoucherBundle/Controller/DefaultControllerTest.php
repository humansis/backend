<?php

namespace VoucherBundle\Tests\Controller;

use Tests\BMSServiceTestCase;
use VoucherBundle\Entity\Vendor;

class DefaultControllerTest extends BMSServiceTestCase
{
    /** @var string $username */
    private $username = "TESTER_PHPUNIT@gmail.com";

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


    public function testCreateVendor() {
        // // First step
        // Get salt for a new user => save the username with the salt in database (user disabled for now)
        // $return = $this->container->get('user.user_service')->getSalt($this->username);
        // Check if the first step has been done correctly
        // $this->assertArrayHasKey('user_id', $return);
        // $this->assertArrayHasKey('salt', $return);

        $body = [
            "name" => 'Carrefour',
            "shop" => 'Fruit and Veg',
            "address" => 'Agusto Figuroa',
            "username" => $this->username,
            'password' => "PSWUNITTEST"
        ];

        var_dump($body);
        // Second step
        // Create the vendor with the email and the salted password. The user should be enable
        $crawler = $this->request('PUT', '/api/wsse/vendors', $body);
        $vendor = json_decode($this->client->getResponse()->getContent());
        var_dump($this->client->getResponse()->isSuccessful());
        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertArrayHasKey('id', $vendor);
        $this->assertArrayHasKey('username', $vendor);
        $this->assertArrayHasKey('email', $vendor);
        $this->assertSame($user['email'], $this->username);

        return $vendor;
    }

    // public function testGetVendor()
    // {
    //     $vendor = new Vendor();

    //     $vendor->setName('Carrefour');
    //     $vendor->setShop('Fruit and Veg');
    //     $vendor->setAddress('Agusto Figuroa');
    //     $vendor->setUsername('relieftesting2018');
    //     $vendor->setPassword('This is a password');

    //     $this->assertEquals($vendor->getName(), 'Carrefour');
    //     $this->assertEquals($vendor->getShop(), 'Fruit and Veg');
    //     $this->assertEquals($vendor->getAddress(), 'Agusto Figuroa');
    //     $this->assertEquals($vendor->getUsername(), 'relieftesting2018');
    //     $this->assertEquals($vendor->getPassword(), 'This is a password');
    // }
}
