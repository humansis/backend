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
            "number_booklets" => 5,
            "individual_value" => 10,
            "currency" => 'USD',
            "number_vouchers" => 3
        ];

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the vendor with the email and the salted password. The user should be enable
        $crawler = $this->request('PUT', '/api/wsse/booklets', $body);
        $booklet = json_decode($this->client->getResponse()->getContent(), true);
        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        // $this->assertArrayHasKey('username', $booklet);
        // $this->assertArrayHasKey('shop', $booklet);

        //only returns the last booklet in batch
        return $booklet;
    }

    /**
     * @throws \Exception
     */
    public function testGetAllBooklets()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/booklets');
        $booklets = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($booklets)) {
            $booklet = $booklets[0];

            $this->assertArrayHasKey('currency', $booklet);
            $this->assertArrayHasKey('vouchers', $booklet);
            $this->assertArrayHasKey('distribution_beneficiary', $booklet);
            $this->assertArrayHasKey('number_vouchers', $booklet);
        } else {
            $this->markTestIncomplete("You currently don't have any booklets in your database.");
        }

        return $booklets[0];
    }

    /**
     * @depends testCreateBooklet
     * @param $newBooklet
     * @return mixed
     */
    public function testGetBooklet($newBooklet)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);


        $crawler = $this->request('GET', '/api/wsse/booklets/' . $newBooklet['id']);
        $booklet = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $booklet);
        $this->assertArrayHasKey('number_vouchers', $booklet);
        $this->assertArrayHasKey('vouchers', $booklet);
        $this->assertArrayHasKey('distribution_beneficiary', $booklet);

        return $booklet;
    }

    /**
     * @depends testCreateBooklet
     * @param $newBooklet
     * @return mixed
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testEditBooklet($newBooklet)
    {
        $currency = 'GBP';
        $body = ["currency" => $currency, "individual_value" => 5];

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('POST', '/api/wsse/booklets/' . $newBooklet['id'], $body);
        $newBookletReceived = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $bookletSearch = $this->em->getRepository(Booklet::class)->find($newBookletReceived['id']);
        $this->assertEquals($bookletSearch->getCurrency(), $currency);

        return $newBookletReceived;
    }


    /**
     * @depends testEditBooklet
     *
     * @param $bookletToDelete
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testDeleteFromDatabase($bookletToDelete)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('DELETE', '/api/wsse/booklets/' . $bookletToDelete['id']);
        $success = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

}