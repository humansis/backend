<?php

namespace CommonBundle\Repository;

use NewApiBundle\Entity\Organization;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\Request\Pagination;

/**
 * OrganizationServicesRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class OrganizationServicesRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Find the organization service by name
     * @param  string $iso3
     * @return QueryBuilder
     */
    public function findOneByService(string $serviceName)
    {
        $qb = $this->createQueryBuilder("os")
                    ->leftJoin('os.service', 's')
                    ->where('s.name = :name')
                    ->setParameter('name', $serviceName);
        
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findByOrganization(Organization $organization, ?Pagination $pagination = null): Paginator
    {
        $qb = $this->createQueryBuilder('os')
            ->where('os.organization = :organization')
            ->setParameter('organization', $organization);

        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        return new Paginator($qb);
    }
}
