<?php
declare(strict_types=1);

namespace NewApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use NewApiBundle\Entity\Import;

class ImportBeneficiaryDuplicityRepository extends EntityRepository
{
    /**
     * @param Import $import
     *
     * @return int
     */
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
        } catch (NoResultException $e) {
            return 0;
        }
    }
}