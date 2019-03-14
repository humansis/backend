<?php
namespace VoucherBundle\Tests\Controller;

use Tests\BMSServiceTestCase;
use VoucherBundle\Entity\Booklet;
use BeneficiaryBundle\Entity\Beneficiary;

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
            "individual_values" => [10, 3, 5],
            "currency" => 'USD',
            "number_vouchers" => 3
        ];

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        $crawler = $this->request('PUT', '/api/wsse/booklets', $body);
        $booklet = json_decode($this->client->getResponse()->getContent(), true);
        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertArrayHasKey('currency', $booklet);
        $this->assertArrayHasKey('vouchers', $booklet);
        $this->assertArrayHasKey('distribution_beneficiary', $booklet);
        $this->assertArrayHasKey('number_vouchers', $booklet);
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
     * @throws \Exception
     */
    public function testDeactivateBooklets()
    {
        $booklets = $this->em->getRepository(Booklet::class)->getActiveBooklets();

        $body = ['bookletCodes' => [$booklets[0]->getCode()]];

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        $crawler = $this->request('POST', '/api/wsse/deactivate-booklets', $body);
        $reponse = json_decode($this->client->getResponse()->getContent(), true);
        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        return $reponse;
    }

    /**
     * @throws \Exception
     */
    public function testDeactivateBooklet()
    {
        $booklets = $this->em->getRepository(Booklet::class)->getActiveBooklets();

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        $crawler = $this->request('DELETE', '/api/wsse/deactivate-booklets/'.$booklets[0]->getId());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        return $response;
    }

    /**
     * @throws \Exception
     */
    public function testGetDeactivatedBooklets()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/deactivated-booklets');
        $booklets = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($booklets)) {
            $booklet = $booklets[0];

            $this->assertArrayHasKey('currency', $booklet);
            $this->assertArrayHasKey('vouchers', $booklet);
            $this->assertArrayHasKey('distribution_beneficiary', $booklet);
            $this->assertArrayHasKey('number_vouchers', $booklet);
        } else {
            $this->markTestIncomplete("You currently don't have any deactivated booklets in your database.");
        }

        return $booklets[0];
    }

    /**
     * @throws \Exception
     */
    public function testUpdatePassword()
    {
        $booklet = $this->em->getRepository(Booklet::class)->findOneBy(['status' => 0]);
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body = [
            'password'  => 'secret-password',
            'code'      => $booklet->getCode(),
        ];

        // Second step
        $crawler = $this->request('POST', '/api/wsse/booklets/update/password', $body);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        return $response;
    }

    /**
     * @throws \Exception
     */
    public function testGetProtectedBooklets()
    {
        $booklet = $this->em->getRepository(Booklet::class)->findOneBy(['password' => 'secret-password']);

        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/protected-booklets');
        $booklets = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($booklets)) {
            $this->assertEquals($booklets[0][$booklet->getCode()], 'secret-password');
        } else {
            $this->markTestIncomplete("You currently don't have any deactivated booklets in your database.");
        }

        return $booklets;
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
        $body = ["currency" => $currency, "number_vouchers" => 4, "individual_values" => [5, 6, 2, 4]];

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

    /**
     * @throws \Exception
     */
    public function testAssignBooklet()
    {
        $booklet = $this->em->getRepository(Booklet::class)->findOneBy(['status' => Booklet::UNASSIGNED]);
        $beneficiary = $this->em->getRepository(Beneficiary::class)->findOneBy([]);
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);
        $body = [
            'code' => $booklet->getCode(),
        ];

        // Second step
        $crawler = $this->request('POST', '/api/wsse/booklets/assign/'.$beneficiary->getId(), $body);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        return $response;
    }

}