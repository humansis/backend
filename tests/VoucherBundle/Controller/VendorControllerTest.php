<?php
namespace VoucherBundle\Tests\Controller;

use Tests\BMSServiceTestCase;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\InputType\SmartcardPurchaseDeprecated;

class VendorControllerTest extends BMSServiceTestCase
{
    /** @var string $username */
    private $username = "vendor-to-create@example.org";

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("serializer");
        parent::setUpFunctionnal();
        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
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
        $this->markTestSkipped('Will be removed with VoucherBundle');
        $crawler = $this->request('GET', '/api/wsse/initialize/' . $this->username);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $this->assertArrayHasKey('user_id', $data);
        $this->assertArrayHasKey('salt', $data);
    }

    /**
     * @throws \Exception
     */
    public function testCreateVendor()
    {
        $this->markTestSkipped('Will be removed with VoucherBundle');

        // First step
        // Get salt for a new vendor => save the username with the salt in database (user disabled for now)
        $return = self::$container->get('user.user_service')->getSaltOld($this->username);
        // Check if the first step has been done correctly
        $this->assertArrayHasKey('user_id', $return);
        $this->assertArrayHasKey('salt', $return);

        $vendor = [
            "username" => $this->username,
            "email" => $this->username,
            "roles" => ["ROLE_ADMIN"],
            "password" => "PSWUNITTEST",
            'salt' => $return['salt'],
            "name" => 'Carrefour',
            "shop" => 'Fruit and Veg',
            "address_number" => '12',
            "address_street" => 'Agusto Figuroa',
            "address_postcode" => '28000',
            "location" => [
                "adm1"=> 1,
                "adm2"=> 1,
                "adm3"=> 1,
                "adm4"=> 1,
                "country_iso3"=> "KHM"
            ],
        ];

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the vendor with the email and the salted password. The user should be enable
        $crawler = $this->request('PUT', '/api/wsse/vendors', $vendor);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $vendor = json_decode($this->client->getResponse()->getContent(), true);
        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('id', $vendor);
        $this->assertArrayHasKey('shop', $vendor);
        $this->assertArrayHasKey('user', $vendor);
        $this->assertArrayHasKey('location', $vendor);
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
        $crawler = $this->request('POST', '/api/wsse/vendor-app/v1/login', $body);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $success = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
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
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $vendors = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($vendors)) {
            $vendor = $vendors[0];

            $this->assertArrayHasKey('user', $vendor);
            $this->assertArrayHasKey('username', $vendor['user']);
            $this->assertArrayHasKey('shop', $vendor);
            $this->assertArrayHasKey('name', $vendor);
            $this->assertArrayHasKey('address_street', $vendor);
            $this->assertArrayHasKey('address_number', $vendor);
            $this->assertArrayHasKey('address_postcode', $vendor);
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
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $vendor = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $vendor);
        $this->assertArrayHasKey('shop', $vendor);
        $this->assertArrayHasKey('name', $vendor);
        $this->assertArrayHasKey('address_street', $vendor);
        $this->assertArrayHasKey('address_number', $vendor);
        $this->assertArrayHasKey('address_postcode', $vendor);
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

        $addressStreet = 'Rosario Romero';
        $addressNumber = '32';
        $addressPostcode = '28500';
        $password = 'PSWUNITTEST';
        $body = [
            'address_number' => $addressNumber,
            'address_street' => $addressStreet,
            'address_postcode' => $addressPostcode,
            'password' => $password,
            "location" => [
                "adm1" => 1,
                "adm2" => 2,
                "adm3" => 3,
                "adm4" => 4,
                "country_iso3" => "KHM",
            ],
            'phone_prefix' => '+34',
            'phone_number' => '675676767',
            'two_factor_authentication' => false
        ];

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('POST', '/api/wsse/vendors/' . $newVendor['id'], $body);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $newVendorReceived = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $vendorSearch = $this->em->getRepository(Vendor::class)->find($newVendorReceived['id']);
        $this->assertEquals($vendorSearch->getAddressStreet(), $addressStreet);
        $this->assertEquals($vendorSearch->getAddressNumber(), $addressNumber);
        $this->assertEquals($vendorSearch->getAddressPostcode(), $addressPostcode);
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
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $newVendorReceived = json_decode($this->client->getResponse()->getContent(), true);

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
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $success = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $this->assertTrue($success);
    }
}
