<?php

declare(strict_types=1);

namespace VoucherBundle\Repository;

use BeneficiaryBundle\Entity\Beneficiary;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use Doctrine\ORM\EntityRepository;
use VoucherBundle\Entity\SmartcardDeposit;

class SmartcardDepositRepository extends EntityRepository
{
    /**
     * @param AssistanceBeneficiary $db
     *
     * @return SmartcardDeposit|null
     */
    public function findByDistributionBeneficiary(AssistanceBeneficiary $db): ?SmartcardDeposit
    {
        $qb = $this->createQueryBuilder('sd')
            ->andWhere('sd.assistanceBeneficiary = :db')
            ->setParameter('db', $db);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Assistance  $assistance
     * @param Beneficiary $beneficiary
     *
     * @return SmartcardDeposit[]
     */
    public function findByAssistanceBeneficiary(Assistance $assistance, Beneficiary $beneficiary)
    {
        $qbr = $this->createQueryBuilder('sd')
            ->join('sd.assistanceBeneficiary', 'ab')
            ->andWhere('ab.assistance = :assistance')
            ->andWhere('ab.beneficiary = :beneficiary')
            ->setParameter('assistance', $assistance)
            ->setParameter('beneficiary', $beneficiary);

        return $qbr->getQuery()->getResult();
    }
}
