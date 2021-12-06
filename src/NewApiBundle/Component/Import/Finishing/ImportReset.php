<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Finishing;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use NewApiBundle\Component\Import\Entity\Import;
use NewApiBundle\Component\Import\Entity\Queue;
use NewApiBundle\Component\Import\Utils\ImportLoggerTrait;
use NewApiBundle\Repository\QueueRepository;
use NewApiBundle\Component\Import\Enum\QueueTransitions;
use NewApiBundle\Workflow\WorkflowTool;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class ImportReset
{
    use ImportLoggerTrait;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var WorkflowInterface
     */
    private $importStateMachine;

    /**
     * @var WorkflowInterface
     */
    private $importQueueStateMachine;

    /**
     * @var ObjectRepository|QueueRepository
     */
    private $queueRepository;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface        $logger,
        WorkflowInterface      $importStateMachine,
        WorkflowInterface      $importQueueStateMachine
    ) {
        $this->em = $em;
        $this->queueRepository = $this->em->getRepository(Queue::class);
        $this->importStateMachine = $importStateMachine;
        $this->importQueueStateMachine = $importQueueStateMachine;
        $this->logger = $logger;
    }

    /**
     * @param Import $conflictImport
     */
    public function reset(Import $conflictImport): void
    {
        $conflictQueue = $this->queueRepository->findBy([
            'import' => $conflictImport,
        ]);
        foreach ($conflictQueue as $item) {
            $this->resetItem($item);
        }
        $this->em->flush();
        $this->logImportInfo($conflictImport,
            "Duplicity checks of ".count($conflictQueue)." queue items reset because finish Import #{$conflictImport->getId()} ({$conflictImport->getTitle()})");
    }

    /**
     * @param Queue $item
     */
    private function resetItem(Queue $item): void
    {
        $item->setIdentityCheckedAt(null);
        $item->setSimilarityCheckedAt(null);

        foreach ($item->getDuplicities() as $duplicity) {
            $this->em->remove($duplicity);
        }

        $this->importQueueStateMachine->apply($item, QueueTransitions::RESET);
        $this->em->persist($item);
    }
}
