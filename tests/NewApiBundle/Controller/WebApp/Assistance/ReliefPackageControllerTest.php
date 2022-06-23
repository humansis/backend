<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Controller\WebApp\Assistance;

use DistributionBundle\Entity\Assistance;
use Exception;
use NewApiBundle\Entity\Assistance\ReliefPackage;
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

    public function testGetOne()
    {
        $reliefPackage = $this->em->getRepository(ReliefPackage::class)->findOneBy([], ['id' => 'asc']);

        $this->request('GET', "/api/basic/web-app/v1/assistances/relief-packages/{$reliefPackage->getId()}");

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
    }

    public function testListReliefPackagesSimple()
    {
        $reliefPackage = $this->em->getRepository(ReliefPackage::class)->findOneBy([], ['id' => 'asc']);

        /** @var Assistance $assitance */
        $assistance = $reliefPackage->getAssistanceBeneficiary()->getAssistance();
        $packageCount = count($this->em->getRepository(ReliefPackage::class)->findByAssistance($assistance));

        $this->request('GET', "/api/basic/web-app/v1/assistances/{$assistance->getId()}/relief-packages");

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertJsonFragment('{
            "totalCount": '.$packageCount.',
            "data": [
                {
                    "id": "*",
                    "state": "*",
                    "modalityType": "*",
                    "amountToDistribute": "*",
                    "amountDistributed": "*",
                    "unit": "*",
                    "createdAt": "*",
                    "distributedAt": "*",
                    "lastModifiedAt": "*"
                }
            ]
        }', $this->client->getResponse()->getContent());
    }

    public function testFilteredList()
    {
        $reliefPackage = $this->em->getRepository(ReliefPackage::class)->findOneBy([], ['id' => 'asc']);

        /** @var Assistance $assitance */
        $assistance = $reliefPackage->getAssistanceBeneficiary()->getAssistance();

        $this->request('GET', "/api/basic/web-app/v1/assistances/{$assistance->getId()}/relief-packages?filter[id][]=".$reliefPackage->getId());

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertJsonFragment('{
            "totalCount": 1,
            "data": [
                {
                    "id": '.$reliefPackage->getId().',
                    "state": "*",
                    "modalityType": "*",
                    "amountToDistribute": "*",
                    "amountDistributed": "*",
                    "unit": "*",
                    "createdAt": "*",
                    "distributedAt": "*",
                    "lastModifiedAt": "*"
                }
            ]
        }', $this->client->getResponse()->getContent());
    }

    public function testDistributeByBeneficiaryId()
    {
        $assistance = $this->em->getRepository(Assistance::class)->findOneBy([], ['id' => 'asc']);

        $this->request('PATCH', "/api/basic/web-app/v1/assistances/{$assistance->getId()}/relief-packages/distribute",
            [
                [
                    "idNumber" => "PIN-1234"
                ]
            ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
    }
}
