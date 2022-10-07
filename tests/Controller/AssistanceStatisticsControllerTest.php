<?php

namespace Tests\Controller;

use Entity\Assistance;
use Entity\User;
use Enum\AssistanceTargetType;
use Repository\AssistanceBeneficiaryRepository;
use Repository\AssistanceRepository;
use Doctrine\Common\Collections\Criteria;
use Exception;
use Component\Assistance\AssistanceFactory;
use Enum\ReliefPackageState;
use Repository\Assistance\ReliefPackageRepository;
use Tests\BMSServiceTestCase;

class AssistanceStatisticsControllerTest extends BMSServiceTestCase
{
    /**
     * @var AssistanceRepository
     */
    private $assistanceRepository;

    /**
     * @var ReliefPackageRepository
     */
    private $reliefPackageRepository;

    /**
     * @var AssistanceBeneficiaryRepository
     */
    private $assistanceBeneficiaryRepository;

    /**
     * @var AssistanceFactory
     */
    private $assistanceFactory;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
        $this->assistanceRepository = self::$container->get(AssistanceRepository::class);
        $this->reliefPackageRepository = self::$container->get(ReliefPackageRepository::class);
        $this->assistanceBeneficiaryRepository = self::$container->get(AssistanceBeneficiaryRepository::class);
        $this->assistanceFactory = self::$container->get(AssistanceFactory::class);
    }

    public function testStatistics()
    {
        /** @var Assistance $assistance */
        $assistance = self::$container->get('doctrine')->getRepository(Assistance::class)
            ->findBy([], ['id' => 'asc'])[0];

        $this->request('GET', '/api/basic/web-app/v1/assistances/' . $assistance->getId() . '/statistics');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "id": ' . $assistance->getId() . ',
            "numberOfBeneficiaries": "*",
            "amountTotal": "*",
            "amountDistributed": "*",
            "amountUsed": "*",
            "amountSent": "*",
            "amountPickedUp": "*"
        }',
            $this->client->getResponse()->getContent()
        );
    }

    public function testList()
    {
        /** @var Assistance $assistance */
        $assistance = self::$container->get('doctrine')->getRepository(Assistance::class)->findBy(
            ['archived' => false],
            ['id' => 'asc']
        )[0];

        $this->request(
            'GET',
            '/api/basic/web-app/v1/assistances/statistics?filter[id][]=' . $assistance->getId(),
            ['country' => 'KHM']
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testStatisticsCheckNumbers(): void
    {
        $assistanceRoot = $this->assistanceRepository->matching(
            Criteria::create()
                ->where(Criteria::expr()->neq('validatedBy', null))
                ->andWhere(Criteria::expr()->eq('completed', false))
                ->andWhere(Criteria::expr()->eq('archived', false))
                ->andWhere(Criteria::expr()->eq('targetType', AssistanceTargetType::INDIVIDUAL))
                ->orderBy(['id' => 'asc'])
        )->first();

        if (!$assistanceRoot) {
            $this->markTestSkipped('There is no suitable assistance for testing in the database.');
        }

        $expectedTotalSum = $this->reliefPackageRepository->sumReliefPackagesAmountByAssistance($assistanceRoot, [
            ReliefPackageState::TO_DISTRIBUTE,
            ReliefPackageState::DISTRIBUTION_IN_PROGRESS,
            ReliefPackageState::DISTRIBUTED,
            ReliefPackageState::EXPIRED,
        ]);
        $expectedDistributed = $this->reliefPackageRepository->sumDistributedReliefPackagesAmountByAssistance(
            $assistanceRoot,
            [
                ReliefPackageState::DISTRIBUTION_IN_PROGRESS,
                ReliefPackageState::DISTRIBUTED,
            ]
        );

        $this->request('GET', '/api/basic/web-app/v1/assistances/' . $assistanceRoot->getId() . '/statistics');
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('amountTotal', $result);
        $this->assertArrayHasKey('amountDistributed', $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('numberOfBeneficiaries', $result);
        $this->assertEquals((float) $expectedTotalSum, (float) $result['amountTotal']);
        $this->assertEquals((float) $expectedDistributed, (float) $result['amountDistributed']);
    }

    public function testCheckStatisticAfterRemoveBnf(): void
    {
        $reliefPackage = $this->reliefPackageRepository->findRandomWithNotValidatedAssistance();
        if (!$reliefPackage) {
            $this->markTestSkipped('There is no suitable assistance for testing in the database.');
        }

        $assistance = $this->assistanceFactory->hydrate($reliefPackage->getAssistanceBeneficiary()->getAssistance());
        $expectedTotalSumBefore = $this->reliefPackageRepository->sumReliefPackagesAmountByAssistance(
            $reliefPackage->getAssistanceBeneficiary()->getAssistance(),
            [
                ReliefPackageState::TO_DISTRIBUTE,
                ReliefPackageState::DISTRIBUTION_IN_PROGRESS,
                ReliefPackageState::DISTRIBUTED,
                ReliefPackageState::EXPIRED,
            ]
        );
        $expectedTotalSumAfter = (float) $expectedTotalSumBefore - (float) $reliefPackage->getAmountToDistribute();

        // remove BNF from assistance
        $this->request(
            'PUT',
            '/api/basic/web-app/v1/assistances/' . $reliefPackage->getAssistanceBeneficiary()->getAssistance()->getId(
            ) . '/assistances-beneficiaries',
            [
                'beneficiaryIds' => [$reliefPackage->getAssistanceBeneficiary()->getBeneficiary()->getId()],
                'justification' => 'test remove',
                'removed' => true,
            ]
        );
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $user = self::$container->get('doctrine')->getRepository(User::class)->findOneBy([]);

        // validate assistance
        $assistance->validate($user);

        // check statistics
        $this->request(
            'GET',
            '/api/basic/web-app/v1/assistances/' . $reliefPackage->getAssistanceBeneficiary()->getAssistance()->getId(
            ) . '/statistics'
        );
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('amountTotal', $result);
        $this->assertEquals($expectedTotalSumAfter, (float) $result['amountTotal']);
    }
}
