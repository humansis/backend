<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\SynchronizationBatch;
use NewApiBundle\Entity\SynchronizationBatch\Deposits;
use NewApiBundle\Enum\SourceType;
use NewApiBundle\Workflow\SynchronizationBatchTransitions;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class SynchronizationBatchControllerTest extends AbstractFunctionalApiTest
{
    public function testCreate()
    {
        $this->client->request('POST', '/api/basic/vendor-app/v1/syncs/deposit', [
            [],
            ["sdfdsfsdf"],
            [
                'reliefPackageId' => 1024,
                'createdAt' => '2010-10-10T00:00:00+0000',
                'smartcardSerialNumber' => 'ASDF123',
                'balanceBefore' => 0,
                'balanceAfter' => 1000,
            ],
        ], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertEmpty($this->client->getResponse()->getContent());
    }

    /**
     * @return int
     */
    public function testGet(): int
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        $sync = new Deposits([]);
        $sync->setSource(SourceType::CLI);
        $sync->setCreatedBy($this->getTestUser(self::USER_TESTER));
        $em->persist($sync);
        $em->flush();

        $this->client->request('GET', '/api/basic/web-app/v1/syncs/'.$sync->getId(), [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

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
        $this->assertArrayHasKey('vendorId', $result);

        return $sync->getId();
    }

    /**
     * @depends testGet
     */
    public function testList()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/syncs?filter[states][]=Uploaded&filter[type]=Deposit&filter[sources][]=CLI', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
            $this->assertArrayHasKey('vendorId', $resultItem);
        }
    }

    /**
     * @depends testGet
     */
    public function testGetNotExists(int $id)
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        $stateMachines = self::$container->get('workflow.registry');
        $repository = $em->getRepository(SynchronizationBatch::class);
        $sync = $repository->find($id);
        $stateMachines->get($sync)->apply($sync, SynchronizationBatchTransitions::COMPLETE_VALIDATION);
        $stateMachines->get($sync)->apply($sync, SynchronizationBatchTransitions::ARCHIVE);
        $em->persist($sync);
        $em->flush();

        $this->client->request('GET', '/api/basic/web-app/v1/syncs/'.$id, [], [], $this->addAuth());
        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

}
