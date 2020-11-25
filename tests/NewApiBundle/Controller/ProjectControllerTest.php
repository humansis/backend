<?php

namespace Tests\NewApiBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Tests\BMSServiceTestCase;

class ProjectControllerTest extends BMSServiceTestCase
{
    /**
     * @throws Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = $this->container->get('test.client');
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testGetProjects()
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/projects');

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $projects = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($projects)) {
            $project = $projects['data'][0];

            $this->assertArrayHasKey('id', $project);
            $this->assertArrayHasKey('iso3', $project);
            $this->assertArrayHasKey('name', $project);
            $this->assertArrayHasKey('notes', $project);
            $this->assertArrayHasKey('target', $project);
            $this->assertArrayHasKey('numberOfHouseholds', $project);
            $this->assertArrayHasKey('endDate', $project);
            $this->assertArrayHasKey('startDate', $project);
            $this->assertArrayHasKey('sectorIds', $project);
            $this->assertArrayHasKey('donorIds', $project);

            $this->assertIsArray($project['donorIds'], 'Donors ids is not array');
            $this->assertIsArray($project['sectorIds'], 'Sectors ids is not array');
        } else {
            $this->markTestIncomplete("You currently don't have any project in your database.");
        }
    }
}
