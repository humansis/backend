<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use NewApiBundle\Entity\DistributedItem;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class DistributedItemControllerTest extends AbstractFunctionalApiTest
{
    public function testFindByHousehold()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        try {
            $householdId = $em->createQueryBuilder()
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
            return;
        }

        $this->client->request('GET', '/api/basic/web-app/v1/households/'.$householdId.'/distributed-items', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        try {
            $beneficiaryId = $em->createQueryBuilder()
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
            return;
        }

        $this->client->request('GET', '/api/basic/web-app/v1/beneficiaries/'.$beneficiaryId.'/distributed-items', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/distributed-items?filter[fulltext]=a&filter[projects][]=1&filter[dateFrom]=2020-01-01&filter[beneficiaryTypes][]=Beneficiary'.
        '&sort[]=dateDistribution.asc&sort[]=beneficiaryId.asc&sort[]=amount.asc', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": "*"
        }', $this->client->getResponse()->getContent());
    }
}
