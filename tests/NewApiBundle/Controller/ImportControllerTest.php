<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use Exception;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Entity\ImportQueue;
use ProjectBundle\Entity\Project;
use Tests\BMSServiceTestCase;

class ImportControllerTest extends BMSServiceTestCase
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
        $this->client = self::$container->get('test.client');
    }

    /**
     * @return integer
     * @throws Exception
     */
    public function testCreate()
    {
        /** @var Project|null $projects */
        $projects = self::$container->get('doctrine')->getRepository(Project::class)->findOneBy([]);

        if (is_null($projects)) {
            $this->markTestSkipped('There needs to be at least one project in system to complete this test');
        }

        $this->request('POST', '/api/basic/imports', [
            'title' => 'test',
            'description' => 'test',
            'projectId' => $projects->getId(),
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('projectId', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('createdBy', $result);
        $this->assertArrayHasKey('createdAt', $result);

        return $result['id'];
    }

    /**
     * @depends testCreate
     *
     * @param int $id
     *
     * @return int
     */
    public function testGet(int $id)
    {
        $this->request('GET', '/api/basic/imports/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('projectId', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('createdBy', $result);
        $this->assertArrayHasKey('createdAt', $result);

        return $id;
    }

    /**
     * @depends testCreate
     */
    public function testList()
    {
        $this->request('GET', '/api/basic/imports?page=1&size=10&sort[]=project.desc');

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
     *
     * @param int $id
     */
    public function testStatusChange(int $id)
    {
        $this->request('PATCH', '/api/basic/imports/'.$id, [
            'status' => 'Integrity Checking',
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
    }

    public function testGetDuplicities()
    {
        /** @var ImportBeneficiaryDuplicity|null $duplicity */
        $duplicity = $this->em->getRepository(ImportBeneficiaryDuplicity::class)->findOneBy([]);

        if (is_null($duplicity)) {
            $this->markTestSkipped('There needs to be at least one import duplicity in system.');
        }

        $importId = $duplicity->getOurs()->getImport()->getId();

        $this->request('GET', "/api/basic/imports/$importId/duplicities");

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": [
                {
                    "id": "*",
                    "itemId": "*",
                    "duplicityCandidateId": "*",
                    "reasons": "*"
                }
            ]}', $this->client->getResponse()->getContent()
        );
    }

    public function testGetImportStatistics()
    {
        /** @var ImportQueue|null $importQueue */
        $importQueue = $this->em->getRepository(ImportQueue::class)->findOneBy([]);

        if (is_null($importQueue)) {
            $this->markTestSkipped('There needs to be at least one import with entries in queue in system.');
        }

        $importId = $importQueue->getImport()->getId();

        $this->request('GET', "/api/basic/imports/$importId/statistics");

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalEntries', $result);
        $this->assertArrayHasKey('amountIntegrityCorrect', $result);
        $this->assertArrayHasKey('amountIntegrityFailed', $result);
        $this->assertArrayHasKey('amountDuplicities', $result);
        $this->assertArrayHasKey('amountDuplicitiesResolved', $result);
        $this->assertArrayHasKey('amountEntriesToImport', $result);
    }

    public function testGetQueueItem()
    {
        /** @var ImportQueue|null $importQueue */
        $importQueue = $this->em->getRepository(ImportQueue::class)->findOneBy([]);

        if (is_null($importQueue)) {
            $this->markTestSkipped('There needs to be at least one import import with entries in queue in system.');
        }

        $importQueueId = $importQueue->getId();

        $this->request('GET', "/api/basic/imports/queue/$importQueueId");

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('values', $result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testResolveDuplicity()
    {
        /** @var ImportBeneficiaryDuplicity|null $importQueue */
        $duplicity = $this->em->getRepository(ImportBeneficiaryDuplicity::class)->findOneBy([]);

        if (is_null($duplicity)) {
            $this->markTestSkipped('There needs to be at least one duplicity with entries in queue in system.');
        }

        $this->request('PATCH', '/api/basic/imports/queue/'.$duplicity->getOurs()->getId(), [
            'status' => 'To Update',
            'acceptedDuplicityId' => $duplicity->getId(),
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
    }

}
