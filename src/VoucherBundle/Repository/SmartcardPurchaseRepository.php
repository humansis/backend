<?php

namespace VoucherBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use VoucherBundle\DTO\PurchaseRedemptionBatch;
use VoucherBundle\DTO\PurchaseRedeemedBatch;
use VoucherBundle\DTO\PurchaseSummary;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardPurchase;
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
            ->groupBy('v.id')
        ;

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
            ->andWhere('p.redeemedAt is null')
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
            ->select('p.redeemedAt as batchDate, COUNT(p.id) as purchaseCount, SUM(pr.value) as purchaseRecordsValue')
            ->join('p.records', 'pr')
            ->where('p.vendor = :vendor')
            ->andWhere('p.redeemedAt is not null')
            ->setParameter('vendor', $vendor)
            ->groupBy('p.redeemedAt');

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
}
