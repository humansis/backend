<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\NationalId;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use Psr\Log\LoggerInterface;

class SimilarityChecker
{
    use ImportLoggerTrait;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function check(Import $import)
    {
        if (ImportState::SIMILARITY_CHECKING !== $import->getState()) {
            throw new \BadMethodCallException('Unable to execute checker. Import is not ready to check.');
        }

        $this->preCheck($import);

        foreach ($this->getItemsToCheck($import) as $i => $item) {
            $this->checkOne($item);

            if ($i % 500 === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();

        $this->postCheck($import);
    }

    protected function checkOne(ImportQueue $item)
    {
        $found = false;

        // TODO: similarity check

        $item->setState(ImportQueueState::TO_CREATE);
        $this->entityManager->persist($item);
    }

    private function preCheck(Import $import)
    {
        $this->entityManager->createQueryBuilder()
            ->update(ImportQueue::class, 'iq')
            ->set('iq.state', '?1')
            ->andWhere('iq.import = ?2')
            ->setParameter('1', ImportQueueState::NEW)
            ->setParameter('2', $import->getId())
            ->getQuery()
            ->execute();
    }

    private function postCheck(Import $import)
    {
        // $isInvalid = $this->isImportQueueInvalid($import);
        // $import->setState($isInvalid ? ImportState::SIMILARITY_CHECK_CORRECT : ImportState::SIMILARITY_CHECK_FAILED);
        $import->setState(ImportState::SIMILARITY_CHECK_CORRECT);

        $this->entityManager->persist($import);
        $this->entityManager->flush();
        $this->logImportDebug($import, "Ended with status ".$import->getState());
    }

    /**
     * @param Import $import
     *
     * @return ImportQueue[]
     */
    private function getItemsToCheck(Import $import): iterable
    {
        return $this->entityManager->getRepository(ImportQueue::class)
            ->findBy(['import' => $import, 'state' => ImportQueueState::NEW]);
    }

    /**
     * @param Import $import
     *
     * @return bool
     */
    public function isImportQueueInvalid(Import $import): bool
    {
        $queue = $this->entityManager->getRepository(ImportQueue::class)
            ->findBy(['import' => $import, 'state' => ImportQueueState::SUSPICIOUS]);

        return count($queue) > 0;
    }
}
