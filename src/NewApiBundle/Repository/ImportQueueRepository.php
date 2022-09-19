<?php
declare(strict_types=1);

namespace NewApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;

class ImportQueueRepository extends EntityRepository
{

    public function lockUnlockedItems(Import $import, $state,int $count, $code)
    {
        $freeIds = $this->findUnlockedIds($import, $state, $count);
        $lockDate = new \DateTimeImmutable();
        $qb = $this->getEntityManager()->createQueryBuilder();
        $builder = $qb
            ->update($this->getEntityName(), 'iq')
            ->set('iq.lockedBy', ':lockedBy')
            ->set('iq.lockedAt',  ':lockDate')

            ->where('iq.import = :import')
            ->andWhere('iq.lockedBy IS NULL OR iq.lockedAt <= :expiredLock')
            ->andWhere('iq.id IN (:freeIds)')
            ->setParameter('lockedBy', $code)
            ->setParameter('import', $import)
            ->setParameter('lockDate', $lockDate)
            ->setParameter('expiredLock', (new \DateTime())->sub(date_interval_create_from_date_string('1 hours')))
            ->setParameter('freeIds', $freeIds);
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

    public function unlockLockedItems(Import $import, $code)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $builder = $qb
            ->update($this->getEntityName(), 'iq')
            ->set('iq.lockedBy', 'NULL')
            ->set('iq.lockedAt',  'NULL')

            ->where('iq.import = :import')
            ->andWhere('iq.lockedBy = :lockedBy')
            ->andWhere('iq.lockedAt IS NOT NULL')
            ->setParameter('lockedBy', $code)
            ->setParameter('import', $import);
        $builder->getQuery()->execute();
    }

    public function findUnlockedIds(Import $import, $state,int $count)
    {
        $qb = $this->createQueryBuilder('iq');
        $builder = $qb
            ->select('iq.id')
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

        $results = $builder->getQuery()->getArrayResult();
        return array_values(array_map(function($item) { return $item['id']; }, $results));
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getTotalByImportAndStatuses(Import $import, array $states): int
    {
        return (int) $this->createQueryBuilder('iq')
            ->select('COUNT(iq)')
            ->andWhere('iq.import = :import')
            ->andWhere('iq.state IN(:states)')
            ->setParameter('import', $import)
            ->setParameter('states', $states)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param Import $import
     * @param string $state
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getTotalByImportAndStatus(Import $import, string $state): int
    {
        return $this->getTotalByImportAndStatuses($import, [$state]);
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

    public function countByImport(Import $import): int
    {
        return (int) $this->createQueryBuilder('iq')
            ->select('COUNT(iq)')
            ->andWhere('iq.import = :import')
            ->setParameter('import', $import)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalResolvedDuplicities(Import $import): int
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
            'identityCheckedAt' => null,
        ], ['id' => 'asc'], $batchSize);
    }

    public function countItemsToIdentityCheck(Import $import): int
    {
        return $this->count([
            'import' => $import,
            'state' => ImportQueueState::VALID,
            'identityCheckedAt' => null,
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
            'similarityCheckedAt' => null,
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
            ->join('iq.householdDuplicities', 'dup')
            ->andWhere('dup.decideAt IS NULL')
            ->setParameter('import', $import)
            ->setParameter('states', [ImportQueueState::SIMILARITY_CANDIDATE])
        ;
        if ($batchSize) {
            $qb->setMaxResults($batchSize);
        }
        return $qb->getQuery()->getResult();
    }

    public function findSingleDuplicityQueues(Import $import): iterable
    {
        $qb = $this->createQueryBuilder('iq')
            ->andWhere('iq.import = :import')
            ->join('iq.householdDuplicities', 'dup')
            ->groupBy('iq.id')
            ->having('count(dup) = 1')
            ->setParameter('import', $import)
        ;
        return $qb->getQuery()->getResult();
    }

    public function save(ImportQueue $importQueue)
    {
        $this->_em->persist($importQueue);
        $this->_em->flush();
    }
}
