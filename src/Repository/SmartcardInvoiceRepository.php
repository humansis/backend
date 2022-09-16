<?php declare(strict_types=1);

namespace Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Request\Pagination;
use Entity\Invoice;
use Entity\Vendor;

/**
 * Class SmartcardInvoiceRepository.
 *
 * @method Invoice find($id)
 */
class SmartcardInvoiceRepository extends EntityRepository
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
