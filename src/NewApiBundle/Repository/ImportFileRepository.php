<?php
declare(strict_types=1);

namespace NewApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportFile;

class ImportFileRepository extends EntityRepository
{

    /**
     * @param Import $import
     *
     * @return ImportFile[]
     */
    public function findInvalid(Import $import): array
    {
        return $this->createQueryBuilder('if')
            ->andWhere('if.import = :import')
            ->andWhere('if.structureViolations IS NULL')
            ->setParameter('import', $import)
            ->getQuery()->getResult();
    }
}
