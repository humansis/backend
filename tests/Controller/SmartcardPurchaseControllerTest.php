<?php

namespace Tests\Controller;

use Doctrine\ORM\NoResultException;
use Exception;
use Tests\BMSServiceTestCase;
use Entity\SmartcardPurchase;
use Entity\Vendor;

class SmartcardPurchaseControllerTest extends BMSServiceTestCase
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

    public function testPurchases()
    {
        $this->request('GET', '/api/basic/web-app/v1/smartcard-purchases');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "totalCount": "*",
            "data": [
                {"id": "*", "beneficiaryId": "*", "value": "*", "currency": "*", "dateOfPurchase": "*"}
            ]
        }',
            $this->client->getResponse()->getContent()
        );
    }

    public function testPurchasesInRedemptionBatch()
    {
        try {
            $batchId = $this->em->createQueryBuilder()
                ->select('srb.id')
                ->from(SmartcardPurchase::class, 'sp')
                ->join('sp.redemptionBatch', 'srb')
                ->where('sp.redemptionBatch IS NOT NULL')
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (NoResultException) {
            $this->markTestSkipped('There is no any redeemed purchase');
        }

        $this->request(
            'GET',
            '/api/basic/web-app/v1/smartcard-redemption-batches/' . $batchId . '/smartcard-purchases'
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "totalCount": "*",
            "data": [
                {"id": "*", "beneficiaryId": "*", "value": "*", "currency": "*", "dateOfPurchase": "*"}
            ]
        }',
            $this->client->getResponse()->getContent()
        );
    }

    public function testPurchasesByRedemptionCandidates()
    {
        $result = $this->em->createQueryBuilder()
            ->select('p.id', 'v.vendorNo', 's.currency')
            ->from(SmartcardPurchase::class, 'sp')
            ->join('sp.vendor', 'v')
            ->join('sp.smartcard', 's')
            ->join('s.deposites', 'sd')
            ->join('sd.reliefPackage', 'pack')
            ->join('pack.assistanceBeneficiary', 'ab')
            ->join('ab.assistance', 'a')
            ->join('a.project', 'p')
            ->where('sp.redemptionBatch IS NULL')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleResult();

        $vendor = $this->em->getRepository(Vendor::class)->findOneBy(
            ['vendorNo' => $result['vendorNo']],
            ['id' => 'asc']
        );

        $this->request(
            'GET',
            '/api/basic/vendor-app/v1/vendors/' . $vendor->getId(
            ) . '/projects/' . $result['id'] . '/currencies/' . $result['currency'] . '/smartcard-purchases'
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '[
            {"id": "*", "beneficiaryId": "*", "value": "*", "currency": "*", "dateOfPurchase": "*"}
        ]',
            $this->client->getResponse()->getContent()
        );
    }
}
