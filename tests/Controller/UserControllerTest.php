<?php

namespace Tests\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Entity\Project;
use Tests\BMSServiceTestCase;

class UserControllerTest extends BMSServiceTestCase
{
    private readonly string $username;

    private readonly string $email;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->username = 'test-username' . time();
        $this->email = time() . 'test@example.org';
    }

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::getContainer()->get('test.client');
    }

    /**
     * @return array
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testInitialize()
    {
        $this->request('POST', '/api/basic/web-app/v1/users/initialize', [
            'username' => $this->username,
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('userId', $result);
        $this->assertArrayHasKey('salt', $result);

        return $result['userId'];
    }

    /**
     * @depends testInitialize
     *
     *
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testCreate(int $userId)
    {
        /** @var Project|null $project */
        $project = self::getContainer()->get('doctrine')->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        if (null === $project) {
            $this->markTestSkipped('There needs to be at least one project in system to complete this test');
        }

        $this->request('POST', '/api/basic/web-app/v1/users/' . $userId, [
            'email' => $this->email,
            'password' => 'password',
            'phonePrefix' => '+420',
            'phoneNumber' => '123456789',
            'countries' => [
                'KHM',
                'SYR',
            ],
            'language' => 'english',
            'roles' => [
                'ROLE_FIELD_OFFICER',
                'ROLE_PROJECT_OFFICER',
            ],
            'projectIds' => [
                $project->getId(),
            ],
            'changePassword' => false,
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('phonePrefix', $result);
        $this->assertArrayHasKey('phoneNumber', $result);
        $this->assertArrayHasKey('countries', $result);
        $this->assertArrayHasKey('language', $result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('projectIds', $result);

        return $result;
    }

    /**
     * @depends testCreate
     *
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testGetSalt(array $result)
    {
        $this->request('GET', '/api/basic/web-app/v1/users/salt/' . $result['username']);

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('userId', $result);
        $this->assertArrayHasKey('salt', $result);
    }

    /**
     * @depends testCreate
     *
     *
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testUpdate(array $result)
    {
        /** @var Project|null $project */
        $project = self::getContainer()->get('doctrine')->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        if (null === $project) {
            $this->markTestSkipped('There needs to be at least one project in system to complete this test');
        }

        $data = [
            'username' => $this->username,
            'email' => $this->email,
            'phonePrefix' => '+420',
            'phoneNumber' => '999999999',
            'countries' => [
                'KHM',
                'SYR',
            ],
            'language' => 'english',
            'roles' => [
                'ROLE_FIELD_OFFICER',
                'ROLE_PROJECT_OFFICER',
            ],
            'projectIds' => [
                $project->getId(),
            ],
            'changePassword' => false,
        ];

        $this->request('PUT', '/api/basic/web-app/v1/users/' . $result['id'], $data);

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('phonePrefix', $result);
        $this->assertArrayHasKey('phoneNumber', $result);
        $this->assertArrayHasKey('countries', $result);
        $this->assertArrayHasKey('language', $result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('projectIds', $result);
        $this->assertArrayHasKey('changePassword', $result);

        $this->assertEquals($data['phoneNumber'], $result['phoneNumber']);

        return $result['id'];
    }

    /**
     * @depends testUpdate
     *
     *
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testGet(int $id)
    {
        $this->request('GET', '/api/basic/web-app/v1/users/' . $id);

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('phonePrefix', $result);
        $this->assertArrayHasKey('phoneNumber', $result);
        $this->assertArrayHasKey('countries', $result);
        $this->assertArrayHasKey('language', $result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('projectIds', $result);

        return $id;
    }

    /**
     * @depends testUpdate
     *
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testList(int $id)
    {
        $this->request('GET', '/api/basic/web-app/v1/users?sort[]=id.desc&filter[fulltext]=test&filter[id][]=' . $id);

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(1, $result['totalCount']);
        $this->assertSame($id, $result['data'][0]['id']);
    }

    /**
     * @depends testGet
     *
     *
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testDelete(int $id)
    {
        $this->request('DELETE', '/api/basic/web-app/v1/users/' . $id);

        $this->assertTrue($this->client->getResponse()->isEmpty());

        return $id;
    }

    /**
     * @depends testDelete
     *
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testGetNotexists(int $id)
    {
        $this->request('GET', '/api/basic/web-app/v1/users/' . $id);

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }
}
