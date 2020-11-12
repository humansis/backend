<?php

namespace VoucherBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use VoucherBundle\DTO\PurchaseDetail;
use VoucherBundle\DTO\PurchaseRedemptionBatch;
use VoucherBundle\DTO\PurchaseRedeemedBatch;
use VoucherBundle\DTO\PurchaseSummary;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\SmartcardRedemptionBatch;
use VoucherBundle\Entity\Vendor;

/**
 * Class SmartcardPurchaseRepository.
 *
 * @method SmartcardPurchase find($id)
 */
class SmartcardPurchaseRepository extends EntityRepository
{
    /**
     * @param Vendor $vendor
     *
     * @return PurchaseSummary
     * @throws NonUniqueResultException
     */
    public function countPurchases(Vendor $vendor): PurchaseSummary
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.id) as purchaseCount, SUM(pr.value) as purchaseRecordsValue, v.id')
            ->join('p.records', 'pr')
            ->join('p.vendor', 'v')
            ->where('p.vendor = :vendor')
            ->setParameter('vendor', $vendor)
            ->groupBy('v.id');

        try {
            $summary = $qb->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return new PurchaseSummary(0, 0);
        }

        return new PurchaseSummary($summary['purchaseCount'], $summary['purchaseRecordsValue'] ?? 0);
    }

    /**
     * @param Vendor $vendor
     *
     * @return PurchaseRedemptionBatch
     * @throws NonUniqueResultException
     */
    public function countPurchasesToRedeem(Vendor $vendor): PurchaseRedemptionBatch
    {
        $idsQuery = $this->createQueryBuilder('p')
            ->select('p.id')
            ->where('p.vendor = :vendor')
            ->andWhere('p.redemptionBatch is null')
            ->setParameter('vendor', $vendor);

        $ids = array_map(function ($result) {
            return (int) $result['id'];
        }, $idsQuery->getQuery()->getScalarResult());

        $valueQuery = $this->createQueryBuilder('p')
            ->select('SUM(pr.value) as purchaseRecordsValue')
            ->join('p.records', 'pr')
            ->join('p.vendor', 'v')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->groupBy('v.id');

        try {
            $summary = $valueQuery->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return new PurchaseRedemptionBatch(0, []);
        }

        return new PurchaseRedemptionBatch($summary['purchaseRecordsValue'], $ids);
    }

    /**
     * @param Vendor $vendor
     *
     * @return PurchaseRedeemedBatch[]
     */
    public function getRedeemBatches(Vendor $vendor): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('b.redeemedAt as batchDate, COUNT(p.id) as purchaseCount, SUM(pr.value) as purchaseRecordsValue')
            ->join('p.redemptionBatch', 'b')
            ->join('p.records', 'pr')
            ->where('p.vendor = :vendor')
            ->andWhere('p.redemptionBatch is not null')
            ->setParameter('vendor', $vendor)
            ->groupBy('b.id');

        $batches = [];
        foreach ($qb->getQuery()->getResult() as $batch) {
            $batches[] = new PurchaseRedeemedBatch(
                $batch['batchDate'],
                $batch['purchaseCount'],
                $batch['purchaseRecordsValue']
            );
        }

        return $batches;
    }

    /**
     * @param SmartcardRedemptionBatch $batch
     *
     * @return PurchaseDetail[]
     */
    public function getDetailsByBatch(SmartcardRedemptionBatch $batch): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select(
                "p.id,
                SUM(pr.value) as purchaseRecordsValue, 
                p.createdAt as purchaseDate,
                person.id as beneficiaryId,
                CONCAT(person.enGivenName, ' ', person.enFamilyName) as beneficiaryEnName,
                CONCAT(person.localGivenName, ' ', person.localFamilyName) as beneficiaryLocalName"
            )
            ->join('p.records', 'pr')
            ->join('p.vendor', 'v')
            ->join('p.smartcard', 's')
            ->join('s.beneficiary', 'b')
            ->join('b.person', 'person')
            ->andWhere('p.redemptionBatch = :batch')
            ->setParameter('batch', $batch)
            ->groupBy('p.id');

        $details = [];
        foreach ($qb->getQuery()->getResult() as $result) {
            $details[] = new PurchaseDetail(
                $result['purchaseDate'],
                $result['beneficiaryId'],
                $result['beneficiaryEnName'],
                $result['beneficiaryLocalName'],
                $result['purchaseRecordsValue']
            );
        }

        return $details;
    }


    /**
     * @param Vendor $vendor
     *
     * @return PurchaseDetail[]
     */
    public function getUsedUnredeemedDetails(Vendor $vendor): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select(
                "p.id,
                SUM(pr.value) as purchaseRecordsValue, 
                p.createdAt as purchaseDate,
                person.id as beneficiaryId,
                CONCAT(person.enGivenName, ' ', person.enFamilyName) as beneficiaryEnName,
                CONCAT(person.localGivenName, ' ', person.localFamilyName) as beneficiaryLocalName"
            )
            ->join('p.records', 'pr')
            ->join('p.vendor', 'v')
            ->join('p.smartcard', 's')
            ->join('s.beneficiary', 'b')
            ->join('b.person', 'person')
            ->andWhere('p.vendor = :vendor')
            ->setParameter('vendor', $vendor)
            ->groupBy('p.id');

        $details = [];
        foreach ($qb->getQuery()->getResult() as $result) {
            $details[] = new PurchaseDetail(
                $result['purchaseDate'],
                $result['beneficiaryId'],
                $result['beneficiaryEnName'],
                $result['beneficiaryLocalName'],
                $result['purchaseRecordsValue']
            );
        }

        return $details;
    }
}
