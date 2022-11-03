<?php

declare(strict_types=1);

namespace Repository;

use Doctrine\ORM\EntityRepository;

class RoleRepository extends EntityRepository
{
    public function findByName($names)
    {
        return $this->createQueryBuilder('role')
            ->andWhere('role.name IN (:names)')
            ->setParameter('names', $names)
            ->getQuery()->getResult();
    }
}
