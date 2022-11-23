<?php

declare(strict_types=1);

namespace Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Entity\Import;

class ImportBeneficiaryDuplicityRepository extends EntityRepository
{
    public function getTotalByImport(Import $import): int
    {
        try {
            return (int) $this->createQueryBuilder('ibd')
                ->select('COUNT(ibd)')
                ->join('ibd.queue', 'iq')
                ->andWhere('iq.import = :import')
                ->setParameter('import', $import)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException) {
            return 0;
        }
    }
}
