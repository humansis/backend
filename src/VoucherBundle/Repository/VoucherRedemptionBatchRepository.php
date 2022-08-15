<?php

namespace VoucherBundle\Repository;

use Doctrine\ORM\EntityRepository;
use NewApiBundle\Entity\Vendor;

class VoucherRedemptionBatchRepository extends EntityRepository
{
    public function getAllByVendor(Vendor $vendor)
    {
        return $this->createQueryBuilder('vrb')
            ->where('vrb.vendor = :vendor')
            ->setParameter('vendor', $vendor)
            ->getQuery()->getResult();
    }
}
