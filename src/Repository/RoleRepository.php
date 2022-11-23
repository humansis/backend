<?php

declare(strict_types=1);

namespace Repository;

use Doctrine\ORM\EntityRepository;

class RoleRepository extends EntityRepository
{
    public function findByCodes(array $codes)
    {
        return $this->createQueryBuilder('role')
            ->andWhere('role.code IN (:codes)')
            ->setParameter('codes', $codes)
            ->getQuery()->getResult();
    }
}
