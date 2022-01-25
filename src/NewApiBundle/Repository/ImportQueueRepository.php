<?php
declare(strict_types=1);

namespace NewApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;

class ImportQueueRepository extends EntityRepository
{
    /**
     * @param Import $import
     * @param string|string[]       $state
     * @param string $code
     * @param int    $count
     */
    public function lock(Import $import, $state, string $code, int $count): void
    {
        $qb = $this->_em->createQueryBuilder();
        $builder = $qb->update("NewApiBundle:ImportQueue", 'iq')
            ->set('iq.lockedAt', ':when')->setParameter('when', new \DateTime())
            ->set('iq.lockedBy', ':code')->setParameter('code', $code)
            ->andWhere('iq.import = :import')
            ->andWhere('iq.lockedBy IS NULL OR iq.lockedAt <= :expiredLock')
            ->setParameter('expiredLock', (new \DateTime())->sub(date_interval_create_from_date_string('1 hours')))
            ->setParameter('import', $import)
            ->setMaxResults($count)
            ;
        if (is_string($state)) {
            $builder
                ->andWhere('iq.state = :state')
                ->setParameter('state', $state)
                ;
        } elseif (is_array($state)) {
            $builder
                ->andWhere('iq.state IN (:states)')
                ->setParameter('states', $state)
            ;
        }
        $builder->getQuery()->execute();
    }

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

    /**
     * @param string $string
     *
     * @return ImportQueue[]
     */
    public function findInContent(Import $import, string $string)
    {
        return $this->createQueryBuilder('iq')
            ->andWhere('iq.import = :import')
            ->andWhere('iq.content LIKE :string')
            ->setParameter('import', $import)
            ->setParameter('string', '%'.$string.'%')
            ->getQuery()->getResult();

    }
    /**
     * @param Import   $import
     * @param int|null $batchSize if null => all
     *
     * @return ImportQueue[]
     */
    public function getItemsToIntegrityCheck(Import $import, ?int $batchSize = null): iterable
    {
        return $this->findBy([
            'import' => $import,
            'state' => ImportQueueState::NEW,
        ], ['id' => 'asc'], $batchSize);
    }

    public function countItemsToIntegrityCheck(Import $import): int
    {
        return $this->count([
            'import' => $import,
            'state' => ImportQueueState::NEW,
        ]);
    }

    /**
     * @param Import   $import
     * @param int|null $batchSize if null => all
     *
     * @return ImportQueue[]
     */
    public function getItemsToIdentityCheck(Import $import, ?int $batchSize = null): iterable
    {
        return $this->findBy([
            'import' => $import,
            'state' => ImportQueueState::VALID,
            'identityCheckedAt' => null
        ], ['id' => 'asc'], $batchSize);
    }

    public function countItemsToIdentityCheck(Import $import): int
    {
        return $this->count([
            'import' => $import,
            'state' => ImportQueueState::VALID,
            'identityCheckedAt' => null
        ]);
    }

    /**
     * @param Import   $import
     * @param int|null $batchSize if null => all
     *
     * @return ImportQueue[]
     */
    public function getItemsToSimilarityCheck(Import $import, ?int $batchSize = null): iterable
    {
        return $this->findBy([
            'import' => $import,
            'state' => [ImportQueueState::VALID, ImportQueueState::UNIQUE_CANDIDATE],
            'similarityCheckedAt' => null
        ], ['id' => 'asc'], $batchSize);
    }

    public function countItemsToSimilarityCheck(Import $import): int
    {
        $qb = $this->createQueryBuilder('iq')
            ->select('count(iq) as cnt')
            ->andWhere('iq.import = :import')
            ->andWhere('iq.state IN (:states)')
            ->andWhere('iq.similarityCheckedAt IS NULL')
            ->setParameter('import', $import)
            ->setParameter('states', [ImportQueueState::VALID, ImportQueueState::UNIQUE_CANDIDATE])
        ;
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Import   $import
     * @param int|null $batchSize if null => all
     *
     * @return ImportQueue[]
     */
    public function getSuspiciousItemsToUserCheck(Import $import, ?int $batchSize = null): iterable
    {
        $qb = $this->createQueryBuilder('iq')
            ->andWhere('iq.import = :import')
            ->andWhere('iq.state IN (:states)')
            ->join('iq.duplicities', 'dup')
            ->andWhere('dup.decideAt IS NULL')
            ->setParameter('import', $import)
            ->setParameter('states', [ImportQueueState::SIMILARITY_CANDIDATE])
        ;
        if ($batchSize) {
            $qb->setMaxResults($batchSize);
        }
        return $qb->getQuery()->getResult();
    }
}
