<?php
declare(strict_types=1);

namespace NewApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;

class ImportQueueRepository extends EntityRepository
{
    public function getTotalByImportAndStatus(Import $import, string $state): int
    {
        return (int) $this->createQueryBuilder('iq')
            ->select('COUNT(iq)')
            ->andWhere('iq.import = :import')
            ->andWhere('iq.state = :state')
            ->setParameter('import', $import)
            ->setParameter('state', $state)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalReadyForSave(Import $import): int
    {
        return (int) $this->createQueryBuilder('iq')
            ->select('COUNT(iq)')
            ->andWhere('iq.import = :import')
            ->andWhere('iq.state IN (:states)')
            ->setParameter('import', $import)
            ->setParameter('states', ImportQueueState::readyToImportStates())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalResolvedDuplicities(Import $import)
    {
        return (int) $this->createQueryBuilder('iq')
            ->select('COUNT(iq)')
            ->join('iq.importBeneficiaryDuplicities', 'ibd')
            ->andWhere('iq.import = :import')
            ->andWhere('iq.state IN (:states)')
            ->setParameter('import', $import)
            ->setParameter('states', ImportQueueState::readyToImportStates())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param Import $import
     *
     * @return ImportQueue[]
     */
    public function getInvalidEntries(Import $import): array
    {
        return $this->createQueryBuilder('iq')
            ->andWhere('iq.import = :import')
            ->andWhere('iq.state = :state')
            ->setParameter('state', ImportQueueState::INVALID)
            ->setParameter('import', $import)
            ->getQuery()
            ->getResult();
    }
}
