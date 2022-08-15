<?php

namespace Tests\NewApiBundle\Controller\WebApp;

use NewApiBundle\Entity\Assistance;
use Exception;
use NewApiBundle\Entity\DistributedItem;
use NewApiBundle\Entity\SmartcardPurchasedItem;
use NewApiBundle\Enum\ModalityType;
use Tests\BMSServiceTestCase;
use NewApiBundle\Entity\SmartcardPurchase;
use NewApiBundle\Entity\Vendor;

class DistributedItemControllerTest extends BMSServiceTestCase
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

    public function testList()
    {
        $itemCount = $this->em->createQueryBuilder()
            ->select('count(i.id)')
            ->from(DistributedItem::class, 'i')
            ->innerJoin('i.project', 'p')
            ->where('p.iso3 = :country')
            ->setParameter('country', 'SYR')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleScalarResult();
        $this->assertGreaterThan(0, $itemCount, "There must be some testing data for /web-app/v1/distributed-items");

        $size = min($itemCount, 5);

        $this->request('GET', "/api/basic/web-app/v1/distributed-items?size=$size&page=1", [], [], [
            'country' => 'SYR'
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": [
                {
                "projectId": "*",
                "beneficiaryId": "*",
                "assistanceId": "*",
                "dateDistribution": "*",
                "commodityId": "*",
                "amount": "*",
                "locationId": "*",
                "adm1Id": "*",
                "adm2Id": "*",
                "adm3Id": "*",
                "adm4Id": "*",
                "carrierNumber": "*",
                "type": "*",
                "modalityType": "*",
                "fieldOfficerId": "*"
                }
            ]
        }', $this->client->getResponse()->getContent());
    }

    public function modalityTypeGenerator(): iterable
    {
        $modalities = [
            ModalityType::MOBILE_MONEY,
            ModalityType::SMART_CARD,
            ModalityType::QR_CODE_VOUCHER,
            ModalityType::FOOD_RATIONS,
            ModalityType::LOAN,
        ];
        foreach ($modalities as $modality) {
            yield $modality => [$modality];
        }
    }

    /**
     * @dataProvider modalityTypeGenerator
     *
     * @param string $modalityType
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function testCompletion(string $modalityType): void
    {
        /** @var Assistance $assistance */
        $assistance = $this->em->createQueryBuilder()
            ->select('a')
            ->from(Assistance::class, 'a')
                ->andWhere('a.validatedBy IS NOT NULL')
            ->innerJoin('a.commodities', 'c')
            ->innerJoin('c.modalityType', 'm')
                ->andWhere('m.name = :modalityType')
                ->setParameter('modalityType', $modalityType)
            ->orderBy('a.id', 'asc')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();

        $this->assertNotNull($assistance, "There must be some testing assistances with modality $modalityType for /web-app/v1/distributed-items");

        $this->request('GET', "/api/basic/web-app/v1/distributed-items?filter[assistances][]=".$assistance->getId(), [], [], [
            'HTTP_COUNTRY' => $assistance->getProject()->getIso3(),
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed'.$this->client->getResponse()->getContent()
        );

        $items = json_decode($this->client->getResponse()->getContent());

        $beneficiaryAmounts = [];
        foreach ($items->data as $distributedItem) {
            $this->assertEquals($assistance->getProject()->getId(), $distributedItem->projectId);
            $this->assertEquals($assistance->getId(), $distributedItem->assistanceId);
            $beneficiaryAmounts[$distributedItem->beneficiaryId] = $distributedItem->amount;
        }

        foreach ($assistance->getDistributionBeneficiaries() as $assistanceBeneficiary) {
            $shouldBeDistributed = 0;
            foreach ($assistanceBeneficiary->getReliefPackages() as $package) {
                $shouldBeDistributed += floatval($package->getAmountDistributed());
            }
            $beneficiaryId = $assistanceBeneficiary->getBeneficiary()->getId();
            if ($shouldBeDistributed > 0) {
                $this->assertEquals($shouldBeDistributed, $beneficiaryAmounts[$beneficiaryId]);
            } else {
                $this->assertArrayNotHasKey($beneficiaryId, $beneficiaryAmounts, "Target {$assistanceBeneficiary->getId()} shouldn't be distributed. Distributed amount=".($beneficiaryAmounts[$beneficiaryId] ?? 'noAmount'));
            }

        }
    }
}
