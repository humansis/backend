<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Controller\OfflineApp\Assistance;

use DistributionBundle\Entity\Assistance;
use Exception;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Enum\ReliefPackageState;
use Tests\BMSServiceTestCase;
use VoucherBundle\Entity\Vendor;

class ReliefPackageControllerTest extends BMSServiceTestCase
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

    public function testReliefPackageDistribution(): void
    {
        /** @var ReliefPackage[] $reliefPackages */
        $reliefPackages = $this->em->getRepository(ReliefPackage::class)->findBy([
            'state' => ReliefPackageState::TO_DISTRIBUTE,
            'amountDistributed' => 0,
        ], ['id' => 'asc'], 3);

        $distributionRequest = [];
        foreach ($reliefPackages as $package) {
            $distributionRequest[] = [
                'id' => $package->getId(),
                'dateDistributed' => (new \DateTime())->format(\DateTimeInterface::ISO8601),
                'amountDistributed' => null,
            ];
        }

        $this->request('PATCH', "/api/basic/offline-app/v1/assistances/relief-packages/distribute", $distributionRequest);
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.json_decode($this->client->getResponse()->getContent(), true)['debug'][0]['message']
        );

        foreach ($reliefPackages as $package) {
            $this->em->refresh($package);
            $this->assertEquals(ReliefPackageState::DISTRIBUTED, $package->getState());
            $this->assertEquals($package->getAmountToDistribute(), $package->getAmountDistributed());
        }

    }
}
