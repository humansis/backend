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
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countPurchases(Vendor $vendor): PurchaseSummary
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id) as purchaseCount, SUM(pr.value) as purchaseRecordsValue')
            ->join('p.records', 'pr')
            ->where('p.vendor = :vendor')
            ->setParameter('vendor', $vendor);

        $summary = $qb->getQuery()->getSingleResult();

        return new PurchaseSummary($summary['purchaseCount'], $summary['purchaseRecordsValue'] ?? 0);
    }

    /**
     * @param Vendor $vendor
     *
     * @return PurchaseRedemptionBatch
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countPurchasesToRedeem(Vendor $vendor): PurchaseRedemptionBatch
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.id')
            ->where('p.vendor = :vendor')
            ->andWhere('p.redeemedAt is null')
            ->setParameter('vendor', $vendor);

        $ids = array_map(function ($result) {
            return (int) $result['id'];
        }, $qb->getQuery()->getScalarResult());

        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id) as purchaseCount, SUM(pr.value) as purchaseRecordsValue')
            ->join('p.records', 'pr')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids);

        $summary = $qb->getQuery()->getSingleResult();

        return new PurchaseRedemptionBatch(
            $summary['purchaseCount'],
            $summary['purchaseRecordsValue'] ?? 0,
            $ids
        );
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
