<?php
namespace VoucherBundle\Tests\Controller;

use Tests\BMSServiceTestCase;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\InputType\SmartcardPurchase;

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
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

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
     */
    public function testGetVendorsPurchases($newVendor): void
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/vendors/' . $newVendor['id'] . '/purchases');
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $summary = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('count', $summary);
        $this->assertArrayHasKey('value', $summary);

        $this->assertIsNumeric($summary['count']);
        $this->assertIsNumeric($summary['value']);
    }

    /**
     * @depends testCreateVendor
     * @param $newVendor
     */
    public function testGetVendorsPurchaseRedeemedBatches($newVendor): void
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $vendorId = $this->em->getRepository(Vendor::class)->findOneBy([], ['id'=>'asc'])->getId();
        $smartcard = $this->em->getRepository(Smartcard::class)->findOneBy([]);
        $purchase = new SmartcardPurchase();
        $purchase->setProducts([[
            'id' => 1,
            'quantity' => 5.9,
            'value' => 1000.05,
        ]]);
        $purchase->setVendorId($vendorId);
        $purchase->setCreatedAt(new \DateTime());
        $purchaseService = $this->container->get('voucher.purchase_service');
        $purchaseService->purchaseSmartcard($smartcard, $purchase);
        $p2 = $purchaseService->purchaseSmartcard($smartcard, $purchase);
        $p3 = $purchaseService->purchaseSmartcard($smartcard, $purchase);
        $p2->setRedeemedAt(new \DateTime());
        $p3->setRedeemedAt(new \DateTime());
        $this->em->persist($p2);
        $this->em->persist($p3);
        $this->em->flush();

        $crawler = $this->request('GET', '/api/wsse/vendors/' . $vendorId . '/redeemed-batches');
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
     * @depends testCreateVendor
     * @param $newVendor
     */
    public function testGetVendorsBatchRedemption($newVendor): void
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/vendors/' . $newVendor['id'] . '/purchases-to-redeem');
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $batch = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($batch);
        $this->assertArrayHasKey('count', $batch);
        $this->assertArrayHasKey('value', $batch);
        $this->assertArrayHasKey('purchases_ids', $batch);

        $this->assertIsInt($batch['count']);
        $this->assertIsNumeric($batch['value']);
        $this->assertIsArray($batch['purchases_ids']);
        foreach ($batch['purchases_ids'] as $id) {
            $this->assertIsInt($id);
        }
    }

    public function testVendorsBatchRedemption(): void
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $vendor = $this->em->getRepository(Vendor::class)->findOneBy([], ['id'=>'asc']);
        $vendorId = $vendor->getId();
        $purchases = $this->em->getRepository(\VoucherBundle\Entity\SmartcardPurchase::class)->findBy([
            'vendor' => $vendor,
            'redeemedAt' => null,
        ]);
        $batchToRedeem = [
            "purchases" => array_map(function (\VoucherBundle\Entity\SmartcardPurchase $purchase) { return $purchase->getId(); }, $purchases),
            "redeemed_at" => "31-12-2020 23:59:59"
        ];

        $crawler = $this->request('POST', '/api/wsse/vendors/' . $vendorId . '/redeem-batch', $batchToRedeem);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($result);
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
