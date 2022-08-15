<?php declare(strict_types=1);

namespace NewApiBundle\Repository;

use NewApiBundle\Entity\Beneficiary;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\InputType\SmartcardPurchaseFilterInputType;
use NewApiBundle\Request\Pagination;
use NewApiBundle\DTO\PurchaseSummary;
use NewApiBundle\Entity\SmartcardPurchase;
use NewApiBundle\Entity\Invoice;
use NewApiBundle\Entity\Vendor;

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

    public function countPurchasesRecordsByInvoice(Invoice $invoice): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('prod.name as name, pr.currency as currency, SUM(pr.value) as value, SUM(pr.quantity) as quantity, prod.unit as unit, MAX(category.type) as categoryType')
            ->join('p.records', 'pr')
            ->join('pr.product', 'prod')
            ->join('prod.productCategory', 'category')
            ->where('p.id IN (:purchases)')
            ->setParameter('purchases', $invoice->getPurchases())
            ->groupBy('prod.name, pr.currency, prod.unit')
        ;

        return $qb->getQuery()->getArrayResult();
    }

    public function sumPurchasesRecordsByCategoryType(Invoice $invoice, $productCategoryType): ?string
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
            ->setParameter('currency', $invoice->getCurrency())
            ->setParameter('batch', $invoice)
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
     * @param Invoice         $invoice
     * @param Pagination|null $pagination
     *
     * @return Paginator|SmartcardPurchase[]
     */
    public function findByBatch(Invoice $invoice, ?Pagination $pagination = null)
    {
        $qbr = $this->createQueryBuilder('sp')
            ->andWhere('sp.redemptionBatch = :redemptionBatch')
            ->setParameter('redemptionBatch', $invoice);

        if ($pagination) {
            $qbr->setMaxResults($pagination->getLimit())
                ->setFirstResult($pagination->getOffset());
        }

        return new Paginator($qbr);
    }

    public function findByBeneficiary(Beneficiary $beneficiary, ?Pagination $pagination = null): Paginator
    {
        $qbr = $this->createQueryBuilder('sp')
            ->innerJoin('sp.smartcard', 'sc')
            ->andWhere('sc.beneficiary = :beneficiary')
            ->setParameter('beneficiary', $beneficiary);

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
}
