<?php


namespace Tests\UserBundle\Controller;


use FOS\UserBundle\Doctrine\UserManager;
use FOS\UserBundle\Security\UserProvider;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Tests\BMSServiceTestCase;
use UserBundle\Entity\User;
use UserBundle\Security\Authentication\Provider\WsseProvider;
use UserBundle\Security\Authentication\Token\WsseUserToken;

class UserControllerTest extends BMSServiceTestCase
{

    /** @var Client $client */
    private $client;


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
        $user           = $this->getTestUser(self::USER_PHPUNIT);
        $token          = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->client->request('GET', '/api/wsse/users');

        $users = json_decode($this->client->getResponse()->getContent(), true);

        if (sizeof($users) > 0)
        {
            $user = $users[0];
            $this->assertArrayHasKey('id', $user);
            $this->assertArrayHasKey('username', $user);
            $this->assertArrayHasKey('email', $user);
            $this->assertArrayHasKey('roles', $user);
            $this->assertArrayHasKey('countries', $user);
            $this->assertArrayHasKey('userProjects', $user);
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
        $crawler = $this->client->request('GET', '/api/wsse/salt?username=tester');

        $salt = $this->client->getResponse()->getContent();
dump($salt);
    }
}