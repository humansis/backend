<?php

namespace Tests\NewApiBundle\Controller;

use Exception;
use ProjectBundle\Entity\Project;
use Tests\BMSServiceTestCase;
use VoucherBundle\Entity\Booklet;

class BookletControllerTest extends BMSServiceTestCase
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
        $this->client = $this->getContainer()->get('test.client');
    }

    public function testCreate()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $project = $this->getContainer()->get('doctrine')->getRepository(Project::class)->findBy([])[0];

        $this->request('POST', '/api/basic/booklets/batches', [
            'iso3' => 'KHM',
            'quantityOfBooklets' => 5,
            'quantityOfVouchers' => 2,
            'values' => [333],
            'projectId' => $project->getId(),
            'password' => null,
            'currency' => 'CZK',
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
    }

    /**
     * @depends testCreate
     */
    public function testGet()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $booklet = $this->getContainer()->get('doctrine')->getRepository(Booklet::class)->findBy([])[0];

        $this->request('GET', '/api/basic/booklets/'.$booklet->getId());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('currency', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('projectId', $result);
        $this->assertArrayHasKey('beneficiaryId', $result);
        $this->assertArrayHasKey('assistanceId', $result);
        $this->assertArrayHasKey('totalValue', $result);
        $this->assertArrayHasKey('deletable', $result);
    }

    /**
     * @depends testCreate
     */
    public function testList()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/booklets?sort[]=value.asc&filter[fulltext]=KHM');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    /**
     * @depends testCreate
     */
    public function testDelete()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $booklet = $this->getContainer()->get('doctrine')->getRepository(Booklet::class)->findBy([
            'status' => Booklet::UNASSIGNED,
        ], ['id' => 'desc'], 1)[0];

        $this->request('DELETE', '/api/basic/booklets/'.$booklet->getId());
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertTrue($this->client->getResponse()->isEmpty(), "Delete request should answer empty body. Returned '{$this->client->getResponse()->getContent()}'");
    }
}
