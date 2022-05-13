<?php

namespace Tests\NewApiBundle\Controller;

use Exception;
use NewApiBundle\Entity\SmartcardPurchasedItem;
use Tests\BMSServiceTestCase;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\Vendor;

class PurchaseItemControllerTest extends BMSServiceTestCase
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

    public function testList()
    {
        $itemCount = $this->em->createQueryBuilder()
            ->select('count(i.id)')
            ->from(SmartcardPurchasedItem::class, 'i')
            ->innerJoin('i.project', 'p')
            ->where('p.iso3 = :country')
            ->setParameter('country', 'SYR')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleScalarResult();
        $this->assertGreaterThan(0, $itemCount, "There must be some testing data for /web-app/v1/smartcard-purchased-items");

        $size = min($itemCount, 5);

        $this->request('GET', "/api/basic/web-app/v1/smartcard-purchased-items?size=$size&page=1", [], [], [
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
                "householdId": "*", 
                "beneficiaryId": "*", 
                "projectId": "*", 
                "assistanceId": "*", 
                "locationId": "*", 
                "adm1Id": "*", 
                "adm2Id": "*", 
                "adm3Id": "*", 
                "adm4Id": "*", 
                "datePurchase": "*", 
                "smartcardCode": "*", 
                "productId": "*", 
                "unit": "*", 
                "value": "*", 
                "currency": "*", 
                "vendorId": "*",
                "invoiceNumber": "*",
                "contractNumber": "*",
                "idNumber": "*"
                }
            ]
        }', $this->client->getResponse()->getContent());
    }
}
