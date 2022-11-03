<?php

declare(strict_types=1);

namespace Tests\Controller;

use Entity\SynchronizationBatch;
use Entity\SynchronizationBatch\Deposits;
use Enum\ReliefPackageState;
use Enum\SourceType;
use InputType\AssistanceFilterInputType;
use Repository\Assistance\ReliefPackageRepository;
use Workflow\SynchronizationBatchTransitions;
use Repository\AssistanceRepository;
use Entity\Assistance\ReliefPackage;
use Tests\BMSServiceTestCase;

class SynchronizationBatchControllerTest extends BMSServiceTestCase
{
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::getContainer()->get('test.client');
    }

    public function testCreate()
    {
        $assistanceRepository = self::getContainer()->get(AssistanceRepository::class);
        $filter = new AssistanceFilterInputType();
        $filter->setFilter(['modalityTypes' => ['Smartcard']]);
        $possible = $assistanceRepository->findByParams('SYR', $filter);
        if ($possible->count() === 0) {
            $this->markTestSkipped('Suitable Smartcard assistance was not found');
        }
        $assistance = $possible
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleResult();

        $reliefPackageRepository = self::getContainer()->get(ReliefPackageRepository::class);
        /** @var ReliefPackage $reliefPackage */
        $reliefPackage = $reliefPackageRepository->findByAssistance($assistance)
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleResult();

        $reliefPackage->setState(ReliefPackageState::TO_DISTRIBUTE);
        $reliefPackageRepository->save($reliefPackage);

        $this->request('POST', '/api/basic/vendor-app/v1/syncs/deposit', [
            [],
            ["sdfdsfsdf"],
            [
                'reliefPackageId' => $reliefPackage->getId(),
                'createdAt' => '2010-10-10T00:00:00+0000',
                'smartcardSerialNumber' => 'AFDF123',
                'balanceBefore' => 0,
                'balanceAfter' => 1000,
            ],
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertEmpty($this->client->getResponse()->getContent());
    }

    public function testGet(): int
    {
        $sync = new Deposits([]);
        $sync->setSource(SourceType::CLI);
        $sync->setCreatedBy($this->getTestUser(self::USER_TESTER));
        $this->em->persist($sync);
        $this->em->flush();

        $this->request('GET', '/api/basic/web-app/v1/syncs/' . $sync->getId());

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
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
        $this->assertArrayHasKey('vendorId', $result);

        return $sync->getId();
    }

    /**
     * @depends testGet
     */
    public function testList()
    {
        $this->request(
            'GET',
            '/api/basic/web-app/v1/syncs?filter[states][]=Uploaded&filter[type]=Deposit&filter[sources][]=CLI'
        );

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
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
            $this->assertArrayHasKey('vendorId', $resultItem);
        }
    }

    /**
     * @depends testGet
     */
    public function testGetNotExists(int $id)
    {
        $stateMachines = self::getContainer()->get('workflow.registry');
        $repository = $this->em->getRepository(SynchronizationBatch::class);
        $sync = $repository->find($id);
        $stateMachines->get($sync)->apply($sync, SynchronizationBatchTransitions::COMPLETE_VALIDATION);
        $stateMachines->get($sync)->apply($sync, SynchronizationBatchTransitions::ARCHIVE);
        $this->em->persist($sync);
        $this->em->flush();

        $this->request('GET', '/api/basic/web-app/v1/syncs/' . $id);
        $this->assertTrue($this->client->getResponse()->isNotFound());
    }
}
