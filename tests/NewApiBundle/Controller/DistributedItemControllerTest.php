<?php

namespace Tests\NewApiBundle\Controller;

use Exception;
use NewApiBundle\Entity\DistributedItem;
use Tests\BMSServiceTestCase;

class DistributedItemControllerTest extends BMSServiceTestCase
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

    public function testFindByHousehold()
    {
        try {
            $householdId = $this->em->createQueryBuilder()
                ->select('b.id')
                ->from(DistributedItem::class, 'di')
                ->join('di.beneficiary', 'b')
                ->where('di.beneficiaryType = :type')
                ->setParameter('type', "Household")
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $exception) {
            $this->markTestSkipped("There is no household in distibuted items.");
        }

        $this->request('GET', '/api/basic/web-app/v1/households/'.$householdId.'/distributed-items');

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
                    "dateExpiration": "*",
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

    public function testFindByBeneficiary()
    {
        try {
            $beneficiaryId = $this->em->createQueryBuilder()
                ->select('b.id')
                ->from(DistributedItem::class, 'di')
                ->join('di.beneficiary', 'b')
                ->where('di.beneficiaryType = :type')
                ->setParameter('type', "Beneficiary")
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $exception) {
            $this->markTestSkipped("There is no beneficiary in distibuted items.");
        }

        $this->request('GET', '/api/basic/web-app/v1/beneficiaries/'.$beneficiaryId.'/distributed-items');

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

    public function testFindByParams()
    {
        $this->request('GET', '/api/basic/web-app/v1/distributed-items?filter[fulltext]=a&filter[projects][]=1&filter[dateFrom]=2020-01-01&filter[beneficiaryTypes][]=Beneficiary'.
        '&sort[]=dateDistribution.asc&sort[]=beneficiaryId.asc&sort[]=amount.asc');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": "*"
        }', $this->client->getResponse()->getContent());
    }
}
