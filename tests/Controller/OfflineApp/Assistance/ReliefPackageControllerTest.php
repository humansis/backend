<?php

declare(strict_types=1);

namespace Tests\Controller\OfflineApp\Assistance;

use DateTime;
use DateTimeInterface;
use Exception;
use Entity\Assistance\ReliefPackage;
use Enum\ReliefPackageState;
use Tests\BMSServiceTestCase;

class ReliefPackageControllerTest extends BMSServiceTestCase
{
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
    }

    public function testReliefPackageDistribution(): void
    {
        /** @var ReliefPackage[] $reliefPackages */
        $reliefPackages = $this->em->getRepository(ReliefPackage::class)->findBy([
            'state' => ReliefPackageState::TO_DISTRIBUTE,
            'amountDistributed' => 0,
        ], ['id' => 'asc'], 3);

        $this->request(
            'PATCH',
            "/api/basic/offline-app/v1/assistances/relief-packages/distribute",
            $this->createDistributionRequest($reliefPackages)
        );
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . json_decode($this->client->getResponse()->getContent(), true)['debug'][0]['message']
        );

        foreach ($reliefPackages as $package) {
            $this->em->refresh($package);
            $this->assertEquals(ReliefPackageState::DISTRIBUTED, $package->getState());
            $this->assertEquals($package->getAmountToDistribute(), $package->getAmountDistributed());
        }
    }

    public function testReliefPackageDoubledDistribution(): void
    {
        /** @var ReliefPackage[] $reliefPackages */
        $reliefPackages = $this->em->getRepository(ReliefPackage::class)->findBy([
            'state' => ReliefPackageState::DISTRIBUTED,
        ], ['id' => 'asc'], 3);

        if (count($reliefPackages) === 0) {
            $this->markTestSkipped('There is not enough relief packages');
        }

        $this->request(
            'PATCH',
            "/api/basic/offline-app/v1/assistances/relief-packages/distribute",
            $this->createDistributionRequest($reliefPackages)
        );
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . json_decode($this->client->getResponse()->getContent(), true)['debug'][0]['message']
        );
        $this->assertEquals(202, $this->client->getResponse()->getStatusCode());

        foreach ($reliefPackages as $package) {
            $this->em->refresh($package);
            $this->assertEquals(ReliefPackageState::DISTRIBUTED, $package->getState());
            $this->assertEquals($package->getAmountToDistribute(), $package->getAmountDistributed());
        }
    }

    /**
     * @param array $reliefPackages
     *
     * @return ReliefPackage[]
     */
    private function createDistributionRequest(array $reliefPackages): array
    {
        $distributionRequest = [];
        foreach ($reliefPackages as $package) {
            $distributionRequest[] = [
                'id' => $package->getId(),
                'dateDistributed' => (new DateTime())->format(DateTimeInterface::ISO8601),
                'amountDistributed' => null,
            ];
        }

        return $distributionRequest;
    }
}
