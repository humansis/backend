<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use NewApiBundle\Entity\SynchronizationBatch;
use NewApiBundle\Enum\SourceType;
use NewApiBundle\Enum\SynchronizationBatchValidationType;
use NewApiBundle\Workflow\SynchronizationBatchTransitions;
use Symfony\Component\Workflow\StateMachine;
use Tests\BMSServiceTestCase;

class SynchronizationBatchControllerTest extends BMSServiceTestCase
{
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    /**
     * @return int
     */
    public function testGet(): int
    {
        $sync = new SynchronizationBatch([], SynchronizationBatchValidationType::DEPOSIT);
        $sync->setSource(SourceType::CLI);
        $sync->setCreatedBy($this->getTestUser(self::USER_TESTER));
        $this->em->persist($sync);
        $this->em->flush();

        $this->request('GET', '/api/basic/web-app/v1/syncs/'.$sync->getId());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('source', $result);
        $this->assertArrayHasKey('createdAt', $result);
        $this->assertArrayHasKey('createdBy', $result);
        $this->assertArrayHasKey('validationType', $result);
        $this->assertArrayHasKey('state', $result);
        $this->assertArrayHasKey('rawData', $result);
        $this->assertArrayHasKey('violations', $result);
        $this->assertArrayHasKey('validatedAt', $result);

        return $sync->getId();
    }

    /**
     * @depends testGet
     */
    public function testList()
    {
        $this->request('GET', '/api/basic/web-app/v1/syncs?filter[states][]=Uploaded&filter[type]=Deposit&filter[sources][]=CLI');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);

        foreach ($result['data'] as $resultItem) {
            $this->assertArrayHasKey('id', $resultItem);
            $this->assertArrayHasKey('source', $resultItem);
            $this->assertArrayHasKey('createdAt', $resultItem);
            $this->assertArrayHasKey('createdBy', $resultItem);
            $this->assertArrayHasKey('validationType', $resultItem);
            $this->assertArrayHasKey('state', $resultItem);
            $this->assertArrayHasKey('rawData', $resultItem);
            $this->assertArrayHasKey('violations', $resultItem);
            $this->assertArrayHasKey('validatedAt', $resultItem);
        }
    }

    /**
     * @depends testGet
     */
    public function testGetNotExists(int $id)
    {
        $stateMachines = self::$container->get('workflow.registry');
        $repository = $this->em->getRepository(SynchronizationBatch::class);
        $sync = $repository->find($id);
        $stateMachines->get($sync)->apply($sync, SynchronizationBatchTransitions::COMPLETE_VALIDATION);
        $stateMachines->get($sync)->apply($sync, SynchronizationBatchTransitions::ARCHIVE);
        $this->em->persist($sync);
        $this->em->flush();

        $this->request('GET', '/api/basic/web-app/v1/syncs/'.$id);
        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

}
