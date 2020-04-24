<?php declare(strict_types = 1);

namespace VoucherBundle\Repository;

use BeneficiaryBundle\Entity\Beneficiary;
use Doctrine\ORM\EntityRepository;

class VoucherRecordRepository extends EntityRepository
{

    /**
     * Returns list of vouchers that was purchased by given beneficiary
     *
     * @param \BeneficiaryBundle\Entity\Beneficiary $beneficiary
     * @return \VoucherBundle\Entity\VoucherRecord[]
     */
    public function findPurchasedByBeneficiary(Beneficiary $beneficiary)
    {
        $qb = $this->createQueryBuilder('vr')
            ->join('vr.voucher', 'v')
            ->join('v.booklet', 'bkl')
            ->join('bkl.distribution_beneficiary', 'db')
            ->join('db.beneficiary', 'b')
            ->where('b.id = :beneficiary')
            ->orderBy('vr.usedAt', 'DESC');

        $qb->setParameter('beneficiary', $beneficiary);

        return $qb->getQuery()->getResult();
    }

}
