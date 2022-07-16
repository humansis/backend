<?php

namespace UserBundle\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;
use InvalidArgumentException;
use NewApiBundle\InputType\UserFilterInputType;
use NewApiBundle\InputType\UserOrderInputType;
use NewApiBundle\Request\Pagination;
use UserBundle\Entity\User;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends \Doctrine\ORM\EntityRepository
{
    public function toggleTwoFA(bool $enable) {
        if ($enable) {
            return;
        }
        $qb = $this->_em->createQueryBuilder();
        $builder = $qb->update("UserBundle:User", 'u')
                ->set('u.twoFactorAuthentication', ':enable')
                ->where('u.twoFactorAuthentication = true')
                ->setParameter('enable', $enable);
        $builder->getQuery()->execute();
    }

    public function findByParams(?UserOrderInputType $orderBy, ?UserFilterInputType $filter, ?Pagination $pagination): Paginator
    {
        $qb = $this->createQueryBuilder("u")
            ->where('u.enabled = :userEnabled')
            ->setParameter('userEnabled', true);

        if (null !== $filter) {
            if ($filter->hasFulltext()) {
                $qb->andWhere('(
                    u.username LIKE :fulltext OR
                    u.email LIKE :fulltext OR
                    u.phonePrefix LIKE :fulltext OR
                    u.phoneNumber LIKE :fulltext
                )')->setParameter('fulltext', '%'.$filter->getFulltext().'%');
            }

            if ($filter->hasIds()) {
                if ($filter->hasIds()) {
                    $qb->andWhere('u.id IN (:ids)');
                    $qb->setParameter('ids', $filter->getIds());
                }
            }

            if ($filter->hasShowVendors()) {
                if ($filter->getShowVendors()) {
                    $qb->andWhere('u.vendor IS NOT NULL');
                } else {
                    $qb->andWhere('u.vendor IS NULL');
                }
            }
        }

        if (null !== $pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        if (null !== $orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                switch ($name) {
                    case UserOrderInputType::SORT_BY_ID:
                        $qb->orderBy('u.id', $direction);
                        break;
                    case UserOrderInputType::SORT_BY_EMAIL:
                        $qb->orderBy('u.email', $direction);
                        break;
                    case UserOrderInputType::SORT_BY_RIGHTS:
                        $qb
                            ->join('u.roles', 'r')
                            ->orderBy('r.name', $direction);
                        break;
                    case UserOrderInputType::SORT_BY_PREFIX:
                        $qb->orderBy('u.phonePrefix', $direction);
                        break;
                    case UserOrderInputType::SORT_BY_PHONE:
                        $qb->orderBy('u.phoneNumber', $direction);
                        break;
                    default:
                        throw new InvalidArgumentException('Invalid order by directive '.$name);
                }
            }
        }

        return new Paginator($qb);
    }

    /**
     * @param User $user
     *
     * @return void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(User $user): void
    {
        $this->_em->persist($user);
        $this->_em->flush();
    }
}
