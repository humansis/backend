<?php
declare(strict_types=1);

namespace Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Entity\Import;

class ImportHouseholdDuplicityRepository extends EntityRepository
{
    public function findByImport(Import $import): Paginator
    {
        $qbr = $this->createQueryBuilder('ibd')
            ->leftJoin('ibd.ours', 'importQueue')
            ->andWhere('importQueue.import = :import')
            ->setParameter('import', $import);

        return new Paginator($qbr);
    }
}
