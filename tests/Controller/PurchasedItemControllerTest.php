<?php

namespace Tests\Controller;

use Doctrine\ORM\NoResultException;
use Exception;
use Entity\DistributedItem;
use Entity\PurchasedItem;
use Tests\BMSServiceTestCase;

class PurchasedItemControllerTest extends BMSServiceTestCase
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

    public function testFindByHousehold()
    {
        try {
            $householdId = $this->em->createQueryBuilder()
                ->select('b.id')
                ->from(PurchasedItem::class, 'pi')
                ->join('pi.beneficiary', 'b')
                ->where('pi.beneficiaryType = :type')
                ->setParameter('type', "Household")
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (NoResultException) {
            $this->markTestSkipped("There is no household in purchased items.");
        }

        $this->request('GET', '/api/basic/web-app/v1/households/' . $householdId . '/purchased-items');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "totalCount": "*",
            "data": "*"
        }',
            $this->client->getResponse()->getContent()
        );
    }

    public function testFindByBeneficiary()
    {
        try {
            $beneficiaryId = $this->em->createQueryBuilder()
                ->select('b.id')
                ->from(PurchasedItem::class, 'pi')
                ->join('pi.beneficiary', 'b')
                ->where('pi.beneficiaryType = :type')
                ->setParameter('type', "Beneficiary")
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (NoResultException) {
            $this->markTestSkipped("There is no beneficiary in purchased items.");
        }

        $this->request('GET', '/api/basic/web-app/v1/beneficiaries/' . $beneficiaryId . '/purchased-items');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "totalCount": "*",
            "data": "*"
        }',
            $this->client->getResponse()->getContent()
        );
    }

    public function testFindByParams()
    {
        $this->request(
            'GET',
            '/api/basic/web-app/v1/purchased-items?filter[fulltext]=a&filter[projects][]=1&filter[beneficiaryTypes][]=Beneficiary&sort[]=value.asc&sort[]=datePurchase.desc'
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "totalCount": "*",
            "data": "*"
        }',
            $this->client->getResponse()->getContent()
        );
    }
}
