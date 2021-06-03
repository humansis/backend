<?php

namespace Tests\NewApiBundle\Controller;

use Exception;
use Tests\BMSServiceTestCase;
use VoucherBundle\Entity\SmartcardPurchase;

class SmartcardPurchaseControllerTest extends BMSServiceTestCase
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

    public function testPurchases()
    {
        $this->request('GET', '/api/basic/web-app/v1/smartcard-purchases');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": [
                {"id": "*", "beneficiaryId": "*", "value": "*", "currency": "*", "dateOfPurchase": "*"}
            ]
        }', $this->client->getResponse()->getContent());
    }

    public function testPurchasesInRedemptionBatch()
    {
        $batchId = $this->em->createQueryBuilder()
            ->select('srb.id')
            ->from(SmartcardPurchase::class, 'sp')
            ->join('sp.redemptionBatch', 'srb')
            ->where('sp.redemptionBatch IS NOT NULL')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleScalarResult();

        $this->request('GET', '/api/basic/web-app/v1/smartcard-redemption-batches/'.$batchId.'/smartcard-purchases');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": [
                {"id": "*", "beneficiaryId": "*", "value": "*", "currency": "*", "dateOfPurchase": "*"}
            ]
        }', $this->client->getResponse()->getContent());
    }

}
