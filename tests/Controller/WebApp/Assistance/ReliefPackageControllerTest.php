<?php

declare(strict_types=1);

namespace Tests\Controller\WebApp\Assistance;

use DataHelper\UserDataHelper;
use DateTimeImmutable;
use Doctrine\ORM\Query\Expr\Join;
use Entity\Assistance;
use Entity\Assistance\ReliefPackage;
use Entity\DistributedItem;
use Entity\Product;
use Entity\Smartcard;
use Entity\SmartcardPurchase;
use Entity\SmartcardPurchaseRecord;
use Entity\Vendor;
use Enum\SmartcardStates;
use Exception;
use Tests\BMSServiceTestCase;
use Tests\ComponentHelper\VendorHelper;
use Utils\VendorService;

class ReliefPackageControllerTest extends BMSServiceTestCase
{
    use VendorHelper;

    private UserDataHelper $userHelper;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::getContainer()->get('test.client');

        $this->userHelper = self::getContainer()->get(UserDataHelper::class);
    }

    public function testGetOne()
    {
        $reliefPackage = $this->em->getRepository(ReliefPackage::class)->findOneBy([], ['id' => 'asc']);

        $this->request('GET', "/api/basic/web-app/v1/assistances/relief-packages/{$reliefPackage->getId()}");

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
    }

    public function testListReliefPackagesSimple()
    {
        $reliefPackage = $this->em->getRepository(ReliefPackage::class)->findOneBy([], ['id' => 'asc']);

        /** @var Assistance $assitance */
        $assistance = $reliefPackage->getAssistanceBeneficiary()->getAssistance();
        $packageCount = is_countable($this->em->getRepository(ReliefPackage::class)->findByAssistance($assistance)) ? count($this->em->getRepository(ReliefPackage::class)->findByAssistance($assistance)) : 0;

        $this->request('GET', "/api/basic/web-app/v1/assistances/{$assistance->getId()}/relief-packages");

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->assertJsonFragment(
            '{
            "totalCount": ' . $packageCount . ',
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
        }',
            $this->client->getResponse()->getContent()
        );
    }

    public function testFilteredList()
    {
        $reliefPackage = $this->em->getRepository(ReliefPackage::class)->findOneBy([], ['id' => 'asc']);

        /** @var Assistance $assitance */
        $assistance = $reliefPackage->getAssistanceBeneficiary()->getAssistance();

        $this->request(
            'GET',
            "/api/basic/web-app/v1/assistances/{$assistance->getId()}/relief-packages?filter[id][]=" . $reliefPackage->getId(
            )
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->assertJsonFragment(
            '{
            "totalCount": 1,
            "data": [
                {
                    "id": ' . $reliefPackage->getId() . ',
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
        }',
            $this->client->getResponse()->getContent()
        );
    }

    public function testDistributeByBeneficiaryId()
    {
        $assistance = $this->em->getRepository(Assistance::class)->findOneBy([], ['id' => 'asc']);

        $this->request(
            'PATCH',
            "/api/basic/web-app/v1/assistances/{$assistance->getId()}/relief-packages/distribute",
            [
                [
                    "idNumber" => "PIN-1234",
                ],
            ]
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
    }
}
