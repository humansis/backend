<?php

declare(strict_types=1);

namespace Tests\Controller\WebApp\Assistance;

use DateTimeImmutable;
use Entity\Assistance;
use Entity\Assistance\ReliefPackage;
use Entity\DistributedItem;
use Entity\Product;
use Entity\Smartcard;
use Entity\SmartcardPurchase;
use Entity\SmartcardPurchaseRecord;
use Entity\Vendor;
use Exception;
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
        $this->client = self::getContainer()->get('test.client');
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

    public function testReliefPackageSum()
    {
        $qb = $this->em->createQueryBuilder();
        /** @var ReliefPackage $reliefPackage */
        $reliefPackage = $qb->select('r')
            ->from(ReliefPackage::class, 'r')
            ->where('r.amountSpent is not null')
            ->andWhere('r.amountSpent < r.amountToDistribute')
            ->getQuery()
            ->getResult()[0];

        $assistance = $reliefPackage->getAssistanceBeneficiary()->getAssistance();
        $spent = (double) $reliefPackage->getAmountSpent();
        $amount = ceil(((double) $reliefPackage->getAmountToDistribute() - $spent) / 2);
        $product = $this->em->getRepository(Product::class)->findOneBy(['id' => 1]);
        $vendor = $this->em->getRepository(Vendor::class)->findOneBy(['location' => $assistance->getLocation()]);
        $smartcard = $this->em->getRepository(Smartcard::class)->findOneBy(
            ['serialNumber' => $reliefPackage->getAssistanceBeneficiary()->getBeneficiary()->getSmartcardSerialNumber()]
        );

        $purchase = SmartcardPurchase::create(
            $smartcard,
            $vendor,
            new DateTimeImmutable(),
            $assistance,
        );
        $purchase->setHash('abc');
        $record = SmartcardPurchaseRecord::create(
            $purchase,
            $product,
            2,
            $amount,
            $reliefPackage->getUnit()
        );

        $this->em->persist($purchase);
        $this->em->persist($record);

        $this->em->flush();

        $item = $this->em->getRepository(DistributedItem::class)->findOneBy([
            'assistance' => $assistance,
            'beneficiary' => $reliefPackage->getAssistanceBeneficiary()->getBeneficiary(),
        ]);

//        dump(
//            $reliefPackage->getId(),
//            $item->getSpent(),
//            $spent,
//            (double)$reliefPackage->getAmountToDistribute(),
//            $amount
//        );

        $this->assertEquals($item->getSpent(), $spent + $amount);
    }
}
