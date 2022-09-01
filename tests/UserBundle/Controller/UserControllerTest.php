<?php


namespace Tests\UserBundle\Controller;

use Symfony\Component\BrowserKit\Client;
use Tests\BMSServiceTestCase;
use UserBundle\Entity\User;

class UserControllerTest extends BMSServiceTestCase
{

    /** @var string $username */
    private $username = "TESTER_PHPUNIT@gmail.com";


    /**
     * @throws \Exception
     */
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("serializer");
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    /**
     * @throws \Exception
     */
    public function testGetSalt()
    {
        $crawler = $this->request('GET', '/api/wsse/initialize/' . $this->username);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('user_id', $data);
        $this->assertArrayHasKey('salt', $data);

        $crawler = $this->request('GET', '/api/wsse/salt/o');
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isClientError(), "Request should fail: ".$this->client->getResponse()->getContent());

        $this->assertTrue(!$this->client->getResponse()->isSuccessful());
        $this->assertTrue(!$this->client->getResponse()->isServerError());
    }

    /**
     * @throws \Exception
     */
    public function testCreateUser()
    {
        // First step
        // Get salt for a new user => save the username with the salt in database (user disabled for now)
        $return = self::$container->get('user.user_service')->getSaltOld($this->username);
        // Check if the first step has been done correctly
        $this->assertArrayHasKey('user_id', $return);
        $this->assertArrayHasKey('salt', $return);

        $body = [
            'username' => $this->username,
            'email' => $this->username,
            'roles' => ['ROLE_ADMIN'],
            'password' => 'PSWUNITTEST',
            'salt' => $return['salt'],
            'phone_prefix' => '+34',
            'phone_number' => '675676767',
            'change_password' => true,
            'two_factor_authentication' => false
        ];

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('PUT', '/api/wsse/users', $body);
        $user = json_decode($this->client->getResponse()->getContent(), true);
        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('id', $user);
        $this->assertArrayHasKey('username', $user);
        $this->assertArrayHasKey('email', $user);
        $this->assertArrayHasKey('phone_prefix', $user);
        $this->assertArrayHasKey('phone_number', $user);
        $this->assertArrayHasKey('two_factor_authentication', $user);
        $this->assertSame($user['email'], $this->username);

        return $user;
    }

    /**
     * @depends testCreateUser
     * @param $newuser
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testLogin($newuser)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body = array(
            'username' => $newuser['username'],
            'password' => 'PSWUNITTEST',
            'creation' => 0
        );

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('POST', '/api/wsse/login', $body);
        $success = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $this->assertTrue(gettype($success) == 'array');
        $this->assertArrayHasKey('id', $success);
        $this->assertArrayHasKey('username', $success);
        $this->assertArrayHasKey('password', $success);
        $this->assertArrayHasKey('roles', $success);
        $this->assertArrayHasKey('email', $success);
    }

    /**
     * @throws \Exception
     */
    public function testCheck()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/check');
        $users = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($users)) {
            $this->assertArrayHasKey('id', $users);
            $this->assertArrayHasKey('username', $users);
            $this->assertArrayHasKey('email', $users);
            $this->assertArrayHasKey('roles', $users);
            $this->assertArrayHasKey('countries', $users);
            $this->assertArrayHasKey('projects', $users);
            $this->assertArrayHasKey('phone_prefix', $users);
            $this->assertArrayHasKey('phone_number', $users);
            $this->assertArrayHasKey('two_factor_authentication', $users);
        } else {
            $this->markTestIncomplete("You currently don't have any user in your database.");
        }
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

        $crawler = $this->request('GET', '/api/wsse/users');
        $users = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($users)) {
            $user = $users[0];

            $this->assertArrayHasKey('id', $user);
            $this->assertArrayHasKey('username', $user);
            $this->assertArrayHasKey('email', $user);
            $this->assertArrayHasKey('roles', $user);
            $this->assertArrayHasKey('countries', $user);
            $this->assertArrayHasKey('projects', $user);
            $this->assertArrayHasKey('phone_prefix', $user);
            $this->assertArrayHasKey('phone_number', $user);
            $this->assertArrayHasKey('two_factor_authentication', $user);
        } else {
            $this->markTestIncomplete("You currently don't have any user in your database.");
        }
    }

    /**
     * @depends testCreateUser
     * @param $newuser
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testShowProject($newuser)
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/users/'. $newuser['id'] .'/projects');
        $projectsUser = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $this->assertTrue(gettype($projectsUser) == 'array');
    }

    /**
     * @depends testCreateUser
     * @param $newuser
     * @return mixed
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testEditUser($newuser)
    {
        $roles = ["ROLE_USER"];

        $body = ["roles" => $roles, 'password' => 'PSWUNITTEST'];

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('POST', '/api/wsse/users/' . $newuser['id'], $body);
        $newUserReceived = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $this->em->clear();

        $userSearch = $this->em->getRepository(User::class)->find($newUserReceived['id']);
        $this->assertEquals($userSearch->getRoles(), $roles);

        return $newUserReceived;
    }

    /**
     * @depends testEditUser
     * @param $userToChange
     * @return mixed
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testChangePassword($userToChange)
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body = ["oldPassword" => "PSWUNITTEST", "newPassword" => "PSWUNITTEST1"];

        $crawler = $this->request('POST', '/api/wsse/users/' . $userToChange['id'] . '/password', $body);
        $newUserReceived = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        $this->em->clear();

        $userSearch = $this->em->getRepository(User::class)->find($userToChange['id']);
        $this->assertSame($userSearch->getPassword(), "PSWUNITTEST1");

        return $newUserReceived;
    }

    /**
     * @depends testEditUser
     *
     * @param $userToDelete
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testDelete($userToDelete)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('DELETE', '/api/wsse/users/' . $userToDelete['id']);
        $success = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $this->assertTrue($success);
    }
}
