<?php

declare(strict_types=1);

namespace Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
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
     * @param Pagination $pagination
     * @return Paginator|Invoice[]
     */
    public function findByVendor(Vendor $vendor, ?Pagination $pagination = null): \Doctrine\ORM\Tools\Pagination\Paginator|array
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

    /**
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Invoice $invoice)
    {
        $this->_em->persist($invoice);
        $this->_em->flush();
    }
}
