<?php

namespace Tests\DistributionBundle\Controller;

use Tests\BMSServiceTestCase;

class CriteriaAssistanceControllerTest extends BMSServiceTestCase
{
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

    /**
     * @return void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testGetCriteria()
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('GET', '/api/wsse/distributions/criteria');
        $criteria = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue(gettype($criteria[0]) == 'array');
        $this->assertTrue(gettype($criteria[1]) == 'array');
        $this->assertTrue(gettype($criteria[2]) == 'array');
        $this->assertTrue(gettype($criteria[3]) == 'array');
        $this->assertTrue(gettype($criteria[4]) == 'array');
    }
}
