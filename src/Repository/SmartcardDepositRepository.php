<?php

declare(strict_types=1);

namespace Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Entity\Assistance;
use Entity\Beneficiary;
use InputType\SmartcardDepositFilterInputType;
use Entity\SmartcardDeposit;

class SmartcardDepositRepository extends EntityRepository
{
    public function findByParams(?SmartcardDepositFilterInputType $filter = null): Paginator
    {
        $qb = $this->createQueryBuilder('sd');

        if ($filter) {
            if ($filter->hasIds()) {
                $qb->andWhere('sd.id IN (:ids)');
                $qb->setParameter('ids', $filter->getIds());
            }
        }

        return new Paginator($qb);
    }

    /**
     * @return SmartcardDeposit[]
     */
    public function getDepositsByBeneficiaryAndAssistance(
        Beneficiary $beneficiary,
        Assistance $assistance
    ): array {
        $qb = $this->createQueryBuilder('sd');
        $qb
            ->leftJoin('sd.reliefPackage', 'rp')
            ->leftJoin('rp.assistanceBeneficiary', 'ab')
            ->andWhere('ab.assistance = :assistanceId')
            ->setParameter('assistanceId', $assistance->getId())
            ->andWhere('ab.beneficiary = :beneficiaryId')
            ->setParameter('beneficiaryId', $beneficiary->getId());

        return $qb->getQuery()->getResult();
    }

    public function findByHash(string $hash): ?SmartcardDeposit
    {
        return $this->findOneBy(['hash' => $hash]);
    }

    public function save(SmartcardDeposit $deposit): void
    {
        $this->_em->persist($deposit);
        $this->_em->flush();
    }
}
