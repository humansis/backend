<?php
namespace VoucherBundle\Tests\Controller;

use Tests\BMSServiceTestCase;
use VoucherBundle\Entity\Booklet;

class BookletControllerTest extends BMSServiceTestCase
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
    public function testCreateBooklet()
    {
        $body = [
            "numberVouchers" => 5,
            "voucherValue" => 10,
            "currency" => 'USD',
            "numberBooklets" => 1
        ];

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the vendor with the email and the salted password. The user should be enable
        $crawler = $this->request('PUT', '/api/wsse/new_booklet', $body);
        $booklet = json_decode($this->client->getResponse()->getContent(), true);
        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        // $this->assertArrayHasKey('username', $booklet);
        // $this->assertArrayHasKey('shop', $booklet);
        return $booklet;
    }

    // /**
    //  * @throws \Exception
    //  */
    // public function testGetAllVendors()
    // {
    //     // Log a user in order to go through the security firewall
    //     $user = $this->getTestUser(self::USER_TESTER);
    //     $token = $this->getUserToken($user);
    //     $this->tokenStorage->setToken($token);

    //     $crawler = $this->request('GET', '/api/wsse/vendors');
    //     $vendors = json_decode($this->client->getResponse()->getContent(), true);

    //     if (!empty($vendors)) {
    //         $vendor = $vendors[0];

    //         $this->assertArrayHasKey('username', $vendor);
    //         $this->assertArrayHasKey('shop', $vendor);
    //         $this->assertArrayHasKey('name', $vendor);
    //         $this->assertArrayHasKey('address', $vendor);
    //     } else {
    //         $this->markTestIncomplete("You currently don't have any vendors in your database.");
    //     }
    // }



    // /**
    //  * @depends testCreateVendor
    //  * @param $newVendor
    //  * @return mixed
    //  */
    // public function testGetVendor($newVendor)
    // {
    //     // Log a user in order to go through the security firewall
    //     $user = $this->getTestUser(self::USER_TESTER);
    //     $token = $this->getUserToken($user);
    //     $this->tokenStorage->setToken($token);


    //     $crawler = $this->request('GET', '/api/wsse/vendors/' . $newVendor['id']);
    //     $vendor = json_decode($this->client->getResponse()->getContent(), true);

    //     $this->assertArrayHasKey('id', $vendor);
    //     $this->assertArrayHasKey('shop', $vendor);
    //     $this->assertArrayHasKey('name', $vendor);
    //     $this->assertArrayHasKey('address', $vendor);
    // }



    // /**
    //  * @depends testCreateVendor
    //  * @param $newVendor
    //  * @return mixed
    //  * @throws \Doctrine\Common\Persistence\Mapping\MappingException
    //  * @throws \Doctrine\ORM\ORMException
    //  * @throws \Doctrine\ORM\OptimisticLockException
    //  */
    // public function testEditVendor($newVendor)
    // {
    //     $address = 'Barbieri 32';
    //     $password = 'PSWUNITTEST';
    //     $body = ["address" => $address, 'password' => $password];

    //     $user = $this->getTestUser(self::USER_TESTER);
    //     $token = $this->getUserToken($user);
    //     $this->tokenStorage->setToken($token);

    //     $crawler = $this->request('POST', '/api/wsse/vendors/' . $newVendor['id'], $body);
    //     $newVendorReceived = json_decode($this->client->getResponse()->getContent(), true);
    //     // var_dump($newVendorReceived);

    //     $this->assertTrue($this->client->getResponse()->isSuccessful());

    //     $vendorSearch = $this->em->getRepository(Vendor::class)->find($newVendorReceived['id']);
    //     $this->assertEquals($vendorSearch->getAddress(), $address);
    //     $this->assertEquals($vendorSearch->getPassword(), $password);

    //     return $newVendorReceived;
    // }

    // /**
    //  * @depends testEditVendor
    //  * @param $vendor
    //  * @return mixed
    //  * @throws \Doctrine\Common\Persistence\Mapping\MappingException
    //  * @throws \Doctrine\ORM\ORMException
    //  * @throws \Doctrine\ORM\OptimisticLockException
    //  */
    // public function testArchiveVendor($vendor)
    // {
    //     $user = $this->getTestUser(self::USER_TESTER);
    //     $token = $this->getUserToken($user);
    //     $this->tokenStorage->setToken($token);

    //     $crawler = $this->request('POST', '/api/wsse/vendors/' . $vendor['id'] . '/archive');
    //     $newVendorReceived = json_decode($this->client->getResponse()->getContent(), true);
    //     $this->assertTrue($this->client->getResponse()->isSuccessful());

    //     $vendorSearch = $this->em->getRepository(Vendor::class)->find($newVendorReceived['id']);
    //     $this->assertEquals($vendorSearch->getArchived(), true);

    //     return $newVendorReceived;
    // }


    // /**
    //  * @depends testEditVendor
    //  *
    //  * @param $vendorToDelete
    //  * @throws \Doctrine\ORM\ORMException
    //  * @throws \Doctrine\ORM\OptimisticLockException
    //  */
    // public function testDeleteFromDatabase($vendorToDelete)
    // {
    //     // Fake connection with a token for the user tester (ADMIN)
    //     $user = $this->getTestUser(self::USER_TESTER);
    //     $token = $this->getUserToken($user);
    //     $this->tokenStorage->setToken($token);

    //     // Second step
    //     // Create the user with the email and the salted password. The user should be enable
    //     $crawler = $this->request('DELETE', '/api/wsse/vendors/' . $vendorToDelete['id']);
    //     $success = json_decode($this->client->getResponse()->getContent(), true);

    //     // Check if the second step succeed
    //     $this->assertTrue($this->client->getResponse()->isSuccessful());
    //     $this->assertTrue($success);
    // }
}