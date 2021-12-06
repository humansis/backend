<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Repository;

use Doctrine\ORM\EntityRepository;
use NewApiBundle\Component\Import\Entity\Import;
use NewApiBundle\Component\Import\Entity\File;

class FileRepository extends EntityRepository
{

    /**
     * @param Import $import
     *
     * @return File[]
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
