<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\SmartcardRedemptionBatch;

class SmartcardRedemptionBatchControllerTest extends AbstractFunctionalApiTest
{
    public function testRedemptionBatchesByVendor()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        $vendorId = $em->createQueryBuilder()
            ->select('v.id')
            ->from(SmartcardRedemptionBatch::class, 'srb')
            ->join('srb.vendor', 'v')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleScalarResult();

        $this->client->request('GET', '/api/basic/web-app/v1/vendors/'.$vendorId.'/smartcard-redemption-batches', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": [
                {"id": "*", "projectId": "*", "contractNumber": "*", "value": "*", "currency": "*", "quantity": "*", "date": "*"}
            ]
        }', $this->client->getResponse()->getContent());
    }

    public function testCreateRedemptionBatchByVendor()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        $vendorId = $em->createQueryBuilder()
            ->select('v.id')
            ->from(SmartcardPurchase::class, 'sp')
            ->join('sp.vendor', 'v')
            ->where('sp.redemptionBatch IS NULL')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleScalarResult();
        $purchaseIds = $em->createQueryBuilder()
            ->select('sp.id')
            ->from(SmartcardPurchase::class, 'sp')
            ->join('sp.vendor', 'v')
            ->where('sp.redemptionBatch IS NULL')
            ->getQuery()
            ->setMaxResults(1)
            ->getResult(Query::HYDRATE_ARRAY);

        $this->client->request('POST', '/api/basic/web-app/v1/vendors/'.$vendorId.'/smartcard-redemption-batches', [
            'purchaseIds' => (array) $purchaseIds[0]['id'],
        ], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "projectId": "*",
            "value": "*",
            "currency": "*",
            "date": "*"
        }', $this->client->getResponse()->getContent());
    }

    public function testRedemptionCandidatesByVendor()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        $vendorId = $em->createQueryBuilder()
            ->select('v.id')
            ->from(SmartcardPurchase::class, 'sp')
            ->join('sp.vendor', 'v')
            ->where('sp.redemptionBatch IS NULL')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleScalarResult();

        $this->client->request('GET', '/api/basic/web-app/v1/vendors/'.$vendorId.'/smartcard-redemption-candidates', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": [
                {"purchaseIds": "*", "projectId": "*", "value": "*", "currency": "*"}
            ]
        }', $this->client->getResponse()->getContent());
    }
}
