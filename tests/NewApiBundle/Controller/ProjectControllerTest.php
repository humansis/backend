<?php

namespace Tests\NewApiBundle\Controller;

use Exception;
use ProjectBundle\DBAL\SectorEnum;
use Tests\BMSServiceTestCase;

class ProjectControllerTest extends BMSServiceTestCase
{
    private $projectName;

    /**
     * @throws Exception
     */
    public function setUp()
    {
        $this->projectName = 'Test project No. '.time();

        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = $this->getContainer()->get('test.client');
    }

    public function testCreate()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('POST', '/api/basic/projects', [
            'name' => $this->projectName,
            'internalId' => 'PT23',
            'iso3' => 'KHM',
            'target' => 10,
            'startDate' => '2010-10-10',
            'endDate' => '2011-10-10',
            'sectors' => [SectorEnum::FOOD_SECURITY],
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('internalId', $result);
        $this->assertArrayHasKey('iso3', $result);
        $this->assertArrayHasKey('notes', $result);
        $this->assertArrayHasKey('target', $result);
        $this->assertArrayHasKey('startDate', $result);
        $this->assertArrayHasKey('endDate', $result);
        $this->assertArrayHasKey('sectors', $result);
        $this->assertArrayHasKey('donorIds', $result);
        $this->assertArrayHasKey('numberOfHouseholds', $result);
        $this->assertArrayHasKey('deletable', $result);
        $this->assertContains(SectorEnum::FOOD_SECURITY, $result['sectors']);
        $this->assertSame([], $result['donorIds']);

        return $result['id'];
    }

    /**
     * @depends testCreate
     */
    public function testSummaries($id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/projects/'.$id.'/summaries?code[]=reached_beneficiaries');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);

        foreach ($result['data'] as $item) {
            $this->assertArrayHasKey('code', $item);
            $this->assertArrayHasKey('value', $item);
        }
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(int $id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('PUT', '/api/basic/projects/'.$id, [
            'name' => $this->projectName,
            'internalId' => 'TPX',
            'iso3' => 'KHM',
            'target' => 10,
            'startDate' => '2010-10-10',
            'endDate' => '2011-10-10',
            'sectors' => [SectorEnum::EARLY_RECOVERY, SectorEnum::CAMP_MANAGEMENT],
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('internalId', $result);
        $this->assertArrayHasKey('iso3', $result);
        $this->assertArrayHasKey('notes', $result);
        $this->assertArrayHasKey('target', $result);
        $this->assertArrayHasKey('startDate', $result);
        $this->assertArrayHasKey('endDate', $result);
        $this->assertArrayHasKey('sectors', $result);
        $this->assertArrayHasKey('donorIds', $result);
        $this->assertArrayHasKey('numberOfHouseholds', $result);
        $this->assertArrayHasKey('deletable', $result);
        $this->assertContains(SectorEnum::EARLY_RECOVERY, $result['sectors']);
        $this->assertContains(SectorEnum::CAMP_MANAGEMENT, $result['sectors']);
        $this->assertNotContains(SectorEnum::FOOD_SECURITY, $result['sectors']);

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testGet(int $id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/projects/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('internalId', $result);
        $this->assertArrayHasKey('iso3', $result);
        $this->assertArrayHasKey('notes', $result);
        $this->assertArrayHasKey('target', $result);
        $this->assertArrayHasKey('startDate', $result);
        $this->assertArrayHasKey('endDate', $result);
        $this->assertArrayHasKey('sectors', $result);
        $this->assertArrayHasKey('donorIds', $result);
        $this->assertArrayHasKey('numberOfHouseholds', $result);
        $this->assertArrayHasKey('deletable', $result);

        return $id;
    }

    /**
     * @depends testGet
     */
    public function testGetList($id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/projects?filter[id][]='.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(1, $result['totalCount']);
        $this->assertSame($id, $result['data'][0]['id']);

        return $id;
    }

    /**
     * @depends testGet
     */
    public function testDelete(int $id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('DELETE', '/api/basic/projects/'.$id);

        $this->assertTrue($this->client->getResponse()->isEmpty());

        return $id;
    }

    /**
     * @depends testDelete
     */
    public function testGetNotexists(int $id)
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/projects/'.$id);

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }
}
