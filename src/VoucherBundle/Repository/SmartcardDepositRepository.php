<?php

declare(strict_types=1);

namespace VoucherBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\InputType\SmartcardDepositFilterInputType;
use VoucherBundle\Entity\SmartcardDeposit;

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
     * @param string $hash
     *
     * @return object|\VoucherBundle\Entity\SmartcardDeposit|null
     */
    public function findByHash(string $hash): ?SmartcardDeposit
    {
        return $this->findOneBy(['hash' => $hash]);
    }

    /**
     * @param SmartcardDeposit $deposit
     *
     * @return void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(SmartcardDeposit $deposit)
    {
        $this->_em->persist($deposit);
        $this->_em->flush();
    }
}
