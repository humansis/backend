<?php

declare(strict_types=1);

namespace VoucherBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\InputType\SmartcardPurchaseFilterInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;
use VoucherBundle\DTO\PurchaseDetail;
use VoucherBundle\DTO\PurchaseRedemptionBatch;
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
     *
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

        return new PurchaseSummary((int) $summary['purchaseCount'], $summary['purchaseRecordsValue'] ?? 0);
    }

    /**
     * @param Vendor $vendor
     *
     * @return PurchaseRedemptionBatch[]
     *
     * @throws NonUniqueResultException
     */
    public function countPurchasesToRedeem(Vendor $vendor): array
    {
        $purchasePreAggregation = "SELECT
            sp.id AS purchaseId,
            (
                SELECT
                    a.project_id AS project_id
                FROM
                    assistance AS a
                        INNER JOIN distribution_beneficiary AS db ON a.id = db.assistance_id
                        LEFT JOIN relief_package abc ON abc.assistance_beneficiary_id=db.id
                        INNER JOIN smartcard_deposit AS sd ON abc.id = sd.relief_package_id AND sd.distributed_at <= sp.used_at
                WHERE s.id = sd.smartcard_id
                ORDER BY sd.distributed_at DESC, sd.id DESC 
                LIMIT 1
            ) AS projectId,
            SUM(spr.value) as purchaseValue,
            spr.currency AS currency,
            sp.vendor_id as vendorId
        FROM
            smartcard AS s
                INNER JOIN smartcard_purchase AS sp on s.id = sp.smartcard_id
                INNER JOIN smartcard_purchase_record AS spr ON sp.id = spr.smartcard_purchase_id
        WHERE sp.redemption_batch_id IS NULL
        GROUP BY sp.id, spr.currency, projectId, vendorId
        ORDER BY sp.id, spr.currency, projectId, vendorId";

        $purchaseValuesAggregation = "SELECT
                pre.currency,
                SUM(pre.purchaseValue) as purchasesValue,
                COUNT(pre.purchaseValue) as purchasesCount,
                pre.projectId
            FROM ($purchasePreAggregation) as pre
            WHERE pre.vendorId = {$vendor->getId()} AND currency IS NOT NULL AND projectId IS NOT NULL
            GROUP BY pre.vendorId, pre.projectId, pre.currency
            ORDER BY pre.vendorId, pre.projectId, pre.currency";

        $stmt = $this->_em->getConnection()->prepare($purchaseValuesAggregation);
        $stmt->execute();
        $batchCandidates = $stmt->fetchAll();

        $projectRepo = $this->_em->getRepository(Project::class);
        $batches = [];
        foreach ($batchCandidates as $candidate) {
            $purchaseIdsAggregation = "SELECT
                    DISTINCT pre.purchaseId as id
                FROM ($purchasePreAggregation) as pre
                WHERE
                    pre.vendorId = {$vendor->getId()} AND
                    pre.projectId = {$candidate['projectId']} AND
                    pre.currency = '{$candidate['currency']}'
                ORDER BY pre.purchaseId";

            $stmt = $this->_em->getConnection()->prepare($purchaseIdsAggregation);
            $stmt->execute();
            $purchaseIds = $stmt->fetchAll();

            $ids = array_map(function ($result) {
                return (int) $result['id'];
            }, $purchaseIds);

            $batches[] = new PurchaseRedemptionBatch(
                $candidate['purchasesValue'],
                $candidate['currency'],
                $projectRepo->find($candidate['projectId']),
                $ids,
                (int) $candidate['purchasesCount'],
            );
        }

        return $batches;
    }

    public function countPurchasesValue(array $purchases)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('SUM(pr.value)')
            ->join('p.records', 'pr')
            ->where('p.id IN (:purchases)')
            ->setParameter('purchases', $purchases);

        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
        } catch (NonUniqueResultException $e) {
            return 0;
        }
    }

    public function countPurchasesRecordsByBatch(SmartcardRedemptionBatch $batch): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('prod.name as name, pr.currency as currency, SUM(pr.value) as value, SUM(pr.quantity) as quantity, prod.unit as unit, MAX(category.type) as categoryType')
            ->join('p.records', 'pr')
            ->join('pr.product', 'prod')
            ->join('prod.productCategory', 'category')
            ->where('p.id IN (:purchases)')
            ->setParameter('purchases', $batch->getPurchases())
            ->groupBy('prod.name, pr.currency, prod.unit')
        ;

        return $qb->getQuery()->getArrayResult();
    }

    public function sumPurchasesRecordsByCategoryType(SmartcardRedemptionBatch $batch, $productCategoryType): ?string
    {
        $qb = $this->createQueryBuilder('p')
            ->select('SUM(pr.value) as value')
            ->join('p.records', 'pr')
            ->join('pr.product', 'prod')
            ->join('prod.productCategory', 'category')
            ->andWhere('IDENTITY(p.redemptionBatch) = :batch')
            ->andWhere('category.type = :type')
            ->andWhere('pr.currency = :currency')
            ->setParameter('type', $productCategoryType)
            ->setParameter('currency', $batch->getCurrency())
            ->setParameter('batch', $batch)
            ->groupBy('p.redemptionBatch')
        ;

        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return "-";
        } catch (NonUniqueResultException $e) {
            return "Error: ".$e->getMessage();
        }
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
                'p.id,
                SUM(pr.value) as purchaseRecordsValue, 
                p.createdAt as purchaseDate,
                person.id as beneficiaryId,
                person.enGivenName,
                person.enFamilyName,
                person.localGivenName,
                person.localFamilyName'
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
                $result['enGivenName'],
                $result['enFamilyName'],
                $result['localGivenName'],
                $result['localFamilyName'],
                $result['purchaseRecordsValue']
            );
        }

        return $details;
    }

    /**
     * @param SmartcardRedemptionBatch $redemptionBatch
     * @param Pagination|null          $pagination
     *
     * @return Paginator|SmartcardPurchase[]
     */
    public function findByBatch(SmartcardRedemptionBatch $redemptionBatch, ?Pagination $pagination = null)
    {
        $qbr = $this->createQueryBuilder('sp')
            ->andWhere('sp.redemptionBatch = :redemptionBatch')
            ->setParameter('redemptionBatch', $redemptionBatch);

        if ($pagination) {
            $qbr->setMaxResults($pagination->getLimit())
                ->setFirstResult($pagination->getOffset());
        }

        return new Paginator($qbr);
    }

    /**
     * @param SmartcardPurchaseFilterInputType $filter
     * @param Pagination|null                  $pagination
     *
     * @return Paginator|SmartcardPurchase[]
     */
    public function findByParams(SmartcardPurchaseFilterInputType $filter, Pagination $pagination): Paginator
    {
        $qbr = $this->createQueryBuilder('sp');

        if ($filter->hasIds()) {
            $qbr->andWhere('sp.id IN (:ids)')
                ->setParameter('ids', $filter->getIds());
        }

        if ($pagination) {
            $qbr->setMaxResults($pagination->getLimit())
                ->setFirstResult($pagination->getOffset());
        }

        return new Paginator($qbr);
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
                'p.id,
                SUM(pr.value) as purchaseRecordsValue, 
                p.createdAt as purchaseDate,
                person.id as beneficiaryId,
                person.enGivenName,
                person.enFamilyName,
                person.localGivenName,
                person.localFamilyName'
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
                $result['enGivenName'],
                $result['enFamilyName'],
                $result['localGivenName'],
                $result['localFamilyName'],
                $result['purchaseRecordsValue']
            );
        }

        return $details;
    }
}
