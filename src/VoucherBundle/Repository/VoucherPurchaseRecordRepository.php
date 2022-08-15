<?php

namespace VoucherBundle\Repository;

use NewApiBundle\Entity\Beneficiary;
use Doctrine\ORM\EntityRepository;
use NewApiBundle\Entity\Voucher;

class VoucherPurchaseRecordRepository extends EntityRepository
{
    /**
     * Returns list of purchases provided by given beneficiary.
     *
     * @param Beneficiary $beneficiary
     *
     * @return Voucher[]
     */
    public function findPurchasedByBeneficiary(Beneficiary $beneficiary)
    {
        $qb = $this->createQueryBuilder('vpr')
            ->join('vpr.voucherPurchase', 'vp')
            ->join('vp.vouchers', 'v')
            ->join('v.booklet', 'bkl')
            ->join('bkl.distribution_beneficiary', 'db')
            ->join('db.beneficiary', 'b')
            ->where('b.id = :beneficiary')
            ->orderBy('vp.createdAt', 'DESC');

        $qb->setParameter('beneficiary', $beneficiary);

        return $qb->getQuery()->getResult();
    }
}
