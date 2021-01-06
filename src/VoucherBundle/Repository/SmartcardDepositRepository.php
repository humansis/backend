<?php

declare(strict_types=1);

namespace VoucherBundle\Repository;

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
}
