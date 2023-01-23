<?php

declare(strict_types=1);

namespace Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Entity\Import;
use InputType\Import\Duplicity\DuplicityFilterInputType;
use Request\Pagination;

class ImportHouseholdDuplicityRepository extends EntityRepository
{
    public function findByImport(Import $import, ?DuplicityFilterInputType $filter, ?Pagination $pagination): Paginator
    {
        $qbr = $this->createQueryBuilder('ibd')
            ->leftJoin('ibd.ours', 'importQueue')
            ->andWhere('importQueue.import = :import')
            ->setParameter('import', $import);

        if ($filter) {
            $qbr->andWhere('ibd.state IN (:states)');
            $qbr->setParameter('states', $filter->getStatus());
        }
        if ($pagination) {
            $qbr->setMaxResults($pagination->getLimit());
            $qbr->setFirstResult($pagination->getOffset());
        }
        return new Paginator($qbr);
    }
}
