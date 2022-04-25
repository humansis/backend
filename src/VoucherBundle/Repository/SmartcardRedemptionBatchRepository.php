<?php declare(strict_types=1);

namespace VoucherBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\Request\Pagination;
use VoucherBundle\Entity\Invoice;
use VoucherBundle\Entity\Vendor;

/**
 * Class SmartcardRedemptionBatchRepository.
 *
 * @method Invoice find($id)
 */
class SmartcardRedemptionBatchRepository extends EntityRepository
{
    /**
     * @param Vendor     $vendor
     * @param Pagination $pagination
     *
     * @return Paginator|Invoice[]
     */
    public function findByVendor(Vendor $vendor, ?Pagination $pagination = null)
    {
        $qbr = $this->createQueryBuilder('srb')
            ->andWhere('srb.vendor = :vendor')
            ->setParameter('vendor', $vendor);

        if ($pagination) {
            $qbr->setMaxResults($pagination->getLimit())
                ->setFirstResult($pagination->getOffset());
        }

        return new Paginator($qbr);
    }
}
