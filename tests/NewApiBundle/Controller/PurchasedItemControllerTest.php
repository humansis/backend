<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\PurchasedItem;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class PurchasedItemControllerTest extends AbstractFunctionalApiTest
{
    public function testFindByHousehold()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        try {
            $householdId = $em->createQueryBuilder()
                ->select('b.id')
                ->from(PurchasedItem::class, 'pi')
                ->join('pi.beneficiary', 'b')
                ->where('pi.beneficiaryType = :type')
                ->setParameter('type', "Household")
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $exception) {
            $this->markTestSkipped("There is no household in purchased items.");
        }

        $this->client->request('GET', '/api/basic/web-app/v1/households/'.$householdId.'/purchased-items', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": "*"
        }', $this->client->getResponse()->getContent());
    }

    public function testFindByBeneficiary()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        try {
            $beneficiaryId = $em->createQueryBuilder()
                ->select('b.id')
                ->from(PurchasedItem::class, 'pi')
                ->join('pi.beneficiary', 'b')
                ->where('pi.beneficiaryType = :type')
                ->setParameter('type', "Beneficiary")
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $exception) {
            $this->markTestSkipped("There is no beneficiary in purchased items.");
        }

        $this->client->request('GET', '/api/basic/web-app/v1/beneficiaries/'.$beneficiaryId.'/purchased-items', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": "*"
        }', $this->client->getResponse()->getContent());
    }

    public function testFindByParams()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/purchased-items?filter[fulltext]=a&filter[projects][]=1&filter[beneficiaryTypes][]=Beneficiary&sort[]=value.asc&sort[]=datePurchase.desc', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": "*"
        }', $this->client->getResponse()->getContent());
    }
}
