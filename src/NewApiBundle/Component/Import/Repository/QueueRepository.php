<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Repository;

use Doctrine\ORM\EntityRepository;
use NewApiBundle\Component\Import\Entity\Import;
use NewApiBundle\Component\Import\Entity\Queue;
use NewApiBundle\Component\Import\Enum\QueueState;

class QueueRepository extends EntityRepository
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
            ->setParameter('states', QueueState::readyToStates())
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
            ->setParameter('states', QueueState::readyToStates())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param Import $import
     *
     * @return Queue[]
     */
    public function getInvalidEntries(Import $import): array
    {
        return $this->createQueryBuilder('iq')
            ->andWhere('iq.import = :import')
            ->andWhere('iq.state = :state')
            ->setParameter('state', QueueState::INVALID)
            ->setParameter('import', $import)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $string
     *
     * @return Queue[]
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
     * @return Queue[]
     */
    public function getItemsToIntegrityCheck(Import $import, ?int $batchSize = null): iterable
    {
        return $this->findBy([
            'import' => $import,
            'state' => QueueState::NEW,
        ], ['id' => 'asc'], $batchSize);
    }

    public function countItemsToIntegrityCheck(Import $import): int
    {
        return $this->count([
            'import' => $import,
            'state' => QueueState::NEW,
        ]);
    }

    /**
     * @param Import   $import
     * @param int|null $batchSize if null => all
     *
     * @return Queue[]
     */
    public function getItemsToIdentityCheck(Import $import, ?int $batchSize = null): iterable
    {
        return $this->findBy([
            'import' => $import,
            'state' => QueueState::VALID,
            'identityCheckedAt' => null
        ], ['id' => 'asc'], $batchSize);
    }

    public function countItemsToIdentityCheck(Import $import): int
    {
        return $this->count([
            'import' => $import,
            'state' => QueueState::VALID,
            'identityCheckedAt' => null
        ]);
    }

    /**
     * @param Import   $import
     * @param int|null $batchSize if null => all
     *
     * @return Queue[]
     */
    public function getItemsToSimilarityCheck(Import $import, ?int $batchSize = null): iterable
    {
        return $this->findBy([
            'import' => $import,
            'state' => [QueueState::VALID, QueueState::UNIQUE_CANDIDATE],
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
            ->setParameter('states', [QueueState::VALID, QueueState::IDENTITY_CANDIDATE])
        ;
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Import   $import
     * @param int|null $batchSize if null => all
     *
     * @return Queue[]
     */
    public function getSuspiciousItemsToUserCheck(Import $import, ?int $batchSize = null): iterable
    {
        $qb = $this->createQueryBuilder('iq')
            ->andWhere('iq.import = :import')
            ->andWhere('iq.state IN (:states)')
            ->join('iq.duplicities', 'dup')
            ->andWhere('dup.decideAt IS NULL')
            ->setParameter('import', $import)
            ->setParameter('states', [QueueState::SIMILARITY_CANDIDATE])
        ;
        if ($batchSize) {
            $qb->setMaxResults($batchSize);
        }
        return $qb->getQuery()->getResult();
    }
}
