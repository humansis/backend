<?php

namespace Repository;

use Doctrine\ORM\EntityRepository;
use Entity\Vendor;
use Entity\VoucherPurchase;

class VoucherPurchaseRepository extends EntityRepository
{
    /**
     * Returns list of purchases in given vendor.
     *
     *
     * @return VoucherPurchase[]
     */
    public function findByVendor(Vendor $vendor)
    {
        $qb = $this->createQueryBuilder('vp')
            ->where('vp.vendor = :vendor')
            ->orderBy('vp.createdAt', 'DESC');

        $qb->setParameter('vendor', $vendor);

        return $qb->getQuery()->getResult();
    }
}
