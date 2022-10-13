<?php

declare(strict_types=1);

namespace Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Entity\Assistance;
use Entity\Beneficiary;
use InputType\SmartcardDepositFilterInputType;
use Entity\SmartcardDeposit;

class SmartcardDepositRepository extends EntityRepository
{
    /**
     * @param SmartcardDepositFilterInputType|null $filter
     *
     * @return Paginator
     */
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
     * @param Beneficiary $beneficiary
     * @param Assistance $assistance
     * @return Assistance\ReliefPackage|null
     * @throws NonUniqueResultException
     */
    public function getByBeneficiaryAndAssistance(
        Beneficiary $beneficiary,
        Assistance $assistance
    ): ?Assistance\ReliefPackage {
        $qb = $this->createQueryBuilder('sd');
        $qb
            ->leftJoin('sd.reliefPackage', 'rp')
            ->leftJoin('rp.assistanceBeneficiary', 'ab')
            ->andWhere('ab.assistance = :assistanceId')
            ->setParameter('assistanceId', $assistance->getId())
            ->andWhere('ab.beneficiary = :beneficiaryId')
            ->setParameter('beneficiaryId', $beneficiary->getId());

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $hash
     *
     * @return object|SmartcardDeposit|null
     */
    public function findByHash(string $hash): ?SmartcardDeposit
    {
        return $this->findOneBy(['hash' => $hash]);
    }

    /**
     * @param SmartcardDeposit $deposit
     *
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(SmartcardDeposit $deposit)
    {
        $this->_em->persist($deposit);
        $this->_em->flush();
    }
}
