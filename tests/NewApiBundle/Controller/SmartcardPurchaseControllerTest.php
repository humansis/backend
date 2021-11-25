<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\Vendor;

class SmartcardPurchaseControllerTest extends AbstractFunctionalApiTest
{
    public function testPurchases()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/smartcard-purchases', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": [
                {"id": "*", "beneficiaryId": "*", "value": "*", "currency": "*", "dateOfPurchase": "*"}
            ]
        }', $this->client->getResponse()->getContent());
    }

    public function testPurchasesInRedemptionBatch()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        $batchId = $em->createQueryBuilder()
            ->select('srb.id')
            ->from(SmartcardPurchase::class, 'sp')
            ->join('sp.redemptionBatch', 'srb')
            ->where('sp.redemptionBatch IS NOT NULL')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleScalarResult();

        $this->client->request('GET', '/api/basic/web-app/v1/smartcard-redemption-batches/'.$batchId.'/smartcard-purchases', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": [
                {"id": "*", "beneficiaryId": "*", "value": "*", "currency": "*", "dateOfPurchase": "*"}
            ]
        }', $this->client->getResponse()->getContent());
    }

    public function testPurchasesByRedemptionCandidates()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        $result = $em->createQueryBuilder()
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

        $vendor = $em->getRepository(Vendor::class)->findOneBy(['vendorNo' => $result['vendorNo']], ['id' => 'asc']);

        $this->client->request('GET', '/api/basic/vendor-app/v1/vendors/'.$vendor->getId().'/projects/'.$result['id'].'/currencies/'.$result['currency'].'/smartcard-purchases', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('[
            {"id": "*", "beneficiaryId": "*", "value": "*", "currency": "*", "dateOfPurchase": "*"}
        ]', $this->client->getResponse()->getContent());
    }
}
