<?php

namespace Tests\NewApiBundle\Controller;

use Doctrine\ORM\Query;
use Exception;
use Tests\BMSServiceTestCase;
use NewApiBundle\Entity\SmartcardPurchase;
use NewApiBundle\Entity\Invoice;

class SmartcardRedemptionBatchControllerTest extends BMSServiceTestCase
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

    public function testRedemptionBatchesByVendor()
    {
        $vendorId = $this->em->createQueryBuilder()
            ->select('v.id')
            ->from(Invoice::class, 'srb')
            ->join('srb.vendor', 'v')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleScalarResult();

        $this->request('GET', '/api/basic/web-app/v1/vendors/'.$vendorId.'/smartcard-redemption-batches');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": [
                {"id": "*", "projectId": "*", "contractNumber": "*", "value": "*", "currency": "*", "quantity": "*", "date": "*"}
            ]
        }', $this->client->getResponse()->getContent());
    }

    public function testCreateRedemptionBatchByVendor()
    {
        $vendorId = $this->em->createQueryBuilder()
            ->select('v.id')
            ->from(SmartcardPurchase::class, 'sp')
            ->join('sp.vendor', 'v')
            ->where('sp.redemptionBatch IS NULL')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleScalarResult();
        $purchaseIds = $this->em->createQueryBuilder()
            ->select('sp.id')
            ->from(SmartcardPurchase::class, 'sp')
            ->join('sp.vendor', 'v')
            ->where('sp.redemptionBatch IS NULL')
            ->getQuery()
            ->setMaxResults(1)
            ->getResult(Query::HYDRATE_ARRAY);

        $this->request('POST', '/api/basic/web-app/v1/vendors/'.$vendorId.'/smartcard-redemption-batches', [
            'purchaseIds' => (array) $purchaseIds[0]['id'],
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "projectId": "*",
            "value": "*",
            "currency": "*",
            "date": "*"
        }', $this->client->getResponse()->getContent());
    }

    public function testRedemptionCandidatesByVendor()
    {
        $vendorId = $this->em->createQueryBuilder()
            ->select('v.id')
            ->from(SmartcardPurchase::class, 'sp')
            ->join('sp.vendor', 'v')
            ->where('sp.redemptionBatch IS NULL')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleScalarResult();

        $this->request('GET', '/api/basic/web-app/v1/vendors/'.$vendorId.'/smartcard-redemption-candidates');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": [
                {"purchaseIds": "*", "projectId": "*", "value": "*", "currency": "*"}
            ]
        }', $this->client->getResponse()->getContent());
    }
}
