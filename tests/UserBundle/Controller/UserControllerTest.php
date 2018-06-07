<?php


namespace Tests\UserBundle\Controller;


use Symfony\Component\BrowserKit\Client;
use Tests\BMSServiceTestCase;
use UserBundle\Entity\User;

class UserControllerTest extends BMSServiceTestCase
{

    /** @var Client $client */
    private $client;
    /** @var string $username */
    private $username = "TESTER_PHPUNIT";


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
    public function testGetUsers()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->client->request('GET', '/api/wsse/users');
        $users = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($users))
        {
            $user = $users[0];

            $this->assertArrayHasKey('id', $user);
            $this->assertArrayHasKey('username', $user);
            $this->assertArrayHasKey('email', $user);
            $this->assertArrayHasKey('roles', $user);
            $this->assertArrayHasKey('countries', $user);
            $this->assertArrayHasKey('user_projects', $user);
        }
        else
        {
            $this->markTestIncomplete("You currently don't have any user in your database.");
        }
    }

    /**
     * @throws \Exception
     */
    public function testGetSalt()
    {
        $crawler = $this->client->request('GET', '/api/wsse/salt/' . $this->username);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('user_id', $data);
        $this->assertArrayHasKey('salt', $data);

        $crawler = $this->client->request('GET', '/api/wsse/salt/o');
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(!$this->client->getResponse()->isSuccessful());
    }

    /**
     * @throws \Exception
     */
    public function testCreateUser()
    {
        // First step
        // Get salt for a new user => save the username with the salt in database (user disabled for now)
        $return = $this->container->get('user.user_service')->getSalt($this->username);
        // Check if the first step has been done correctly
        $this->assertArrayHasKey('user_id', $return);
        $this->assertArrayHasKey('salt', $return);

        $body = [
            "username" => $this->username,
            "email" => $this->username . "@gmail.com",
            "password" => "PSWUNITTEST"
        ];

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->client->request('PUT', '/api/wsse/users', $body);
        $user = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertArrayHasKey('id', $user);
        $this->assertArrayHasKey('username', $user);
        $this->assertArrayHasKey('email', $user);
        $this->assertSame($user['email'], $this->username . "@gmail.com");

    }

    /**
     * @depends testCreateUser
     * @throws \Exception
     */
    public function testEditUser()
    {
        $user = $this->em->getRepository(User::class)->findOneByUsername(self::USER_TESTER);
        if (!$user instanceof User)
            $this->fail("ISSUE : This test must be executed after the createTest");

        $timestamp = (new \DateTime())->getTimestamp();
        $email = $this->username . "@gmailedited." . $timestamp;

        $body = ["email" => $email];

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->client->request('POST', '/api/wsse/users/' . $user->getId(), $body);
        $user = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->em->clear();

        $userSearch = $this->em->getRepository(User::class)->find($user['id']);
        $this->assertSame($userSearch->getEmail(), $email);
    }

    /**
     * @afterClass
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function tearDown()
    {
        $user = $this->em->getRepository(User::class)->findOneByUsername($this->username);
        if ($user instanceof User)
        {
            $this->em->remove($user);
            $this->em->flush();
        }
    }
}