<?php
namespace VoucherBundle\Tests\Controller;

use Tests\BMSServiceTestCase;
use VoucherBundle\Entity\Vendor;

class VendorControllerTest extends BMSServiceTestCase
{
    /** @var string $username */
    private $username = "VENDOR_PHPUNIT@gmail.com";

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


    // Need to do this step for the next test to pass
    /**
     * @throws \Exception
     */
   /**
     * @throws \Exception
     */
    public function testGetSalt()
    {
        $crawler = $this->request('GET', '/api/wsse/initialize/' . $this->username);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('user_id', $data);
        $this->assertArrayHasKey('salt', $data);
    }

    /**
     * @throws \Exception
     */
    public function testCreateVendor()
    {
        // First step
        // Get salt for a new vendor => save the username with the salt in database (user disabled for now)
        $return = $this->container->get('user.user_service')->getSalt($this->username);
        // Check if the first step has been done correctly
        $this->assertArrayHasKey('user_id', $return);
        $this->assertArrayHasKey('salt', $return);

        $vendor = [
            "username" => $this->username,
            "email" => $this->username,
            "rights" => "ROLE_ADMIN",
            "password" => "PSWUNITTEST",
            'salt' => $return['salt'],
            "name" => 'Carrefour',
            "shop" => 'Fruit and Veg',
            "address" => 'Agusto Figuroa'
        ];

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the vendor with the email and the salted password. The user should be enable
        $crawler = $this->request('PUT', '/api/wsse/vendors', $vendor);
        $vendor = json_decode($this->client->getResponse()->getContent(), true);
        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertArrayHasKey('id', $vendor);
        $this->assertArrayHasKey('shop', $vendor);
        $this->assertArrayHasKey('user', $vendor);
        $this->assertArrayHasKey('username', $vendor['user']);
        $this->assertSame($vendor['user']['username'], $this->username);

        return $vendor;
    }

    /**
     * @depends testCreateVendor
     * @param $newVendor
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testLogin($newVendor)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body = array(
            'username' => $newVendor['user']['username'],
            'salted_password' => 'PSWUNITTEST',
            'creation' => 0
        );

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('POST', '/api/wsse/login_app', $body);
        $success = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertTrue(gettype($success) == 'array');
        $this->assertArrayHasKey('id', $success);
        $this->assertArrayHasKey('user', $success);
        $this->assertArrayHasKey('shop', $success);
    }

    /**
     * @throws \Exception
     */
    public function testGetAllVendors()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/vendors');
        $vendors = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($vendors)) {
            $vendor = $vendors[0];

            $this->assertArrayHasKey('user', $vendor);
            $this->assertArrayHasKey('username', $vendor['user']);
            $this->assertArrayHasKey('shop', $vendor);
            $this->assertArrayHasKey('name', $vendor);
            $this->assertArrayHasKey('address', $vendor);
        } else {
            $this->markTestIncomplete("You currently don't have any vendors in your database.");
        }
    }


    /**
     * @depends testCreateVendor
     * @param $newVendor
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetVendor($newVendor)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);


        $crawler = $this->request('GET', '/api/wsse/vendors/' . $newVendor['id']);
        $vendor = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $vendor);
        $this->assertArrayHasKey('shop', $vendor);
        $this->assertArrayHasKey('name', $vendor);
        $this->assertArrayHasKey('address', $vendor);
    }


    /**
     * @depends testCreateVendor
     * @param $newVendor
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testEditVendor($newVendor)
    {
        $address = 'Barbieri 32';
        $password = 'PSWUNITTEST';
        $body = ["address" => $address, 'password' => $password];

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('POST', '/api/wsse/vendors/' . $newVendor['id'], $body);
        $newVendorReceived = json_decode($this->client->getResponse()->getContent(), true);
        // var_dump($newVendorReceived);

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $vendorSearch = $this->em->getRepository(Vendor::class)->find($newVendorReceived['id']);
        $this->assertEquals($vendorSearch->getAddress(), $address);
        $this->assertEquals($vendorSearch->getUser()->getPassword(), $password);

        return $newVendorReceived;
    }

    /**
     * @depends testEditVendor
     * @param $vendor
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testArchiveVendor($vendor)
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('POST', '/api/wsse/vendors/' . $vendor['id'] . '/archive');
        $newVendorReceived = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $vendorSearch = $this->em->getRepository(Vendor::class)->find($newVendorReceived['id']);
        $this->assertEquals($vendorSearch->getArchived(), true);

        return $newVendorReceived;
    }


    /**
     * @depends testEditVendor
     *
     * @param $vendorToDelete
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testDeleteFromDatabase($vendorToDelete)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('DELETE', '/api/wsse/vendors/' . $vendorToDelete['id']);
        $success = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertTrue($success);
    }
}