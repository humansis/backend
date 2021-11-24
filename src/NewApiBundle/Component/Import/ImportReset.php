<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Workflow\ImportQueueTransitions;
use NewApiBundle\Workflow\ImportTransitions;
use NewApiBundle\Workflow\WorkflowTool;
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
     * @var ObjectRepository|ImportQueueRepository
     */
    private $queueRepository;

    public function __construct(EntityManagerInterface $em, WorkflowInterface $importStateMachine, WorkflowInterface $importQueueStateMachine)
    {
        $this->em = $em;
        $this->queueRepository = $this->em->getRepository(ImportQueue::class);
        $this->importStateMachine = $importStateMachine;
        $this->importQueueStateMachine = $importQueueStateMachine;
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
            WorkflowTool::checkAndApply($this->importQueueStateMachine, $item, [ImportQueueTransitions::RESET]);
        }
        $this->em->flush();
        $this->logImportInfo($conflictImport,
            "Duplicity checks of ".count($conflictQueue)." queue items reset because finish Import #{$conflictImport->getId()} ({$conflictImport->getTitle()})");
    }
}
