<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Finishing;

use BadMethodCallException;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Utils\HouseholdService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use InvalidArgumentException;
use NewApiBundle\Component\Import\Finishing;
use NewApiBundle\Component\Import\ImportLoggerTrait;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportBeneficiary;
use NewApiBundle\Entity\ImportHouseholdDuplicity;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Utils\Concurrency\ConcurrencyProcessor;
use NewApiBundle\Workflow\ImportQueueTransitions;
use NewApiBundle\Workflow\ImportTransitions;
use ProjectBundle\Entity\Project;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use UserBundle\Entity\User;

class ItemFinishService
{
    use ImportLoggerTrait;

    const LOCK_BATCH = 100;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /** @var HouseholdDecoratorBuilder */
    private $householdDecoratorBuilder;

    /**
     * @var WorkflowInterface
     */
    private $importStateMachine;

    /**
     * @var WorkflowInterface
     */
    private $importQueueStateMachine;

    /**
     * @var HouseholdService
     */
    private $householdService;

    /**
     * @var ImportQueueRepository
     */
    private $queueRepository;

    /** @var integer */
    private $totalBatchSize;

    /** @var ManagerRegistry */
    private $managerRegistry;

    public function __construct(
        int                                 $totalBatchSize,
        EntityManagerInterface              $em,
        HouseholdService                    $householdService,
        LoggerInterface                     $logger,
        WorkflowInterface                   $importStateMachine,
        WorkflowInterface                   $importQueueStateMachine,
        Finishing\HouseholdDecoratorBuilder $householdDecoratorBuilder,
        ImportQueueRepository               $queueRepository,
        ManagerRegistry                     $managerRegistry
    ) {
        $this->em = $em;
        $this->importStateMachine = $importStateMachine;
        $this->importQueueStateMachine = $importQueueStateMachine;
        $this->householdService = $householdService;
        $this->queueRepository = $queueRepository;
        $this->logger = $logger;
        $this->totalBatchSize = $totalBatchSize;
        $this->householdDecoratorBuilder = $householdDecoratorBuilder;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param Import $import
     *
     * @throws EntityNotFoundException
     */
    public function import(Import $import)
    {
        if ($import->getState() !== ImportState::IMPORTING) {
            throw new BadMethodCallException('Wrong import status');
        }

        $statesToFinish = [
            ImportQueueState::TO_CREATE,
            ImportQueueState::TO_UPDATE,
            ImportQueueState::TO_LINK,
            ImportQueueState::TO_IGNORE,
        ];

        $itemProcessor = new ConcurrencyProcessor();
        $itemProcessor
            ->setBatchSize(self::LOCK_BATCH)
            ->setMaxResultsToProcess($this->totalBatchSize)
            ->setCountAllCallback(function() use ($import, $statesToFinish) {
                return $this->queueRepository->count([
                    'import' => $import,
                    'state' => $statesToFinish,
                ]);
            })
            ->setLockBatchCallback(function($runCode, $batchSize) use ($import, $statesToFinish) {
                $this->lockImportQueue($import, $statesToFinish, $runCode, $batchSize);
            })
            ->setUnlockItemsCallback(function($runCode) use ($import) {
                $this->queueRepository->unlockLockedItems($import, $runCode);
            })
            ->setBatchItemsCallback(function($runCode) use ($import) {
                return $this->queueRepository->findBy([
                    'import' => $import,
                    'lockedBy' => $runCode,
                ]);
            })
            ->setBatchCleanupCallback(function() {
                $this->em->flush();
            })
            ->processItems(function(ImportQueue $item) use ($import) {
                try {
                    switch ($item->getState()) {
                        case ImportQueueState::TO_CREATE:
                            $this->finishCreationQueue($item, $import);
                            break;
                        case ImportQueueState::TO_UPDATE:
                            $this->finishUpdateQueue($item, $import);
                            break;
                        case ImportQueueState::TO_IGNORE:
                        case ImportQueueState::TO_LINK:
                            $this->finishLinkQueue($item, $import);
                            break;
                    }
                    $this->em->persist($item);
                } catch (\Throwable $anyException) {
                    if (!$this->em->isOpen()) {
                        $this->em = $this->managerRegistry->resetManager();
                        $item = $this->em->getRepository(ImportQueue::class)->find($item->getId());
                    }
                    $this->logger->error($anyException->getMessage());
                    $item->setUnexpectedError(UnexpectedError::create($item->getState(), $anyException));
                    $this->importQueueStateMachine->apply($item, ImportQueueTransitions::FAIL_UNEXPECTED);
                    $this->em->persist($item);
                    throw $anyException;
                }

            });
        $this->em->flush();
        if ($this->importStateMachine->can($import, ImportTransitions::FINISH)) {
            $this->importStateMachine->apply($import, ImportTransitions::FINISH);

            $this->resetOtherImports($import);
        }

        $this->em->flush();
    }

    private function lockImportQueue(Import $import, $state, string $code, int $count)
    {
        $this->queueRepository->lockUnlockedItems($import, $state, $count, $code);
    }

    /**
     * @param Import $import
     */
    public function resetOtherImports(Import $import)
    {
        if ($import->getState() !== ImportState::FINISHED) {
            throw new BadMethodCallException('Wrong import status');
        }

        $importConflicts = $this->em->getRepository(Import::class)->getConflictingImports($import);
        $this->logImportInfo($import, count($importConflicts)." conflicting imports to reset duplicity checks");
        foreach ($importConflicts as $conflictImport) {
            if ($this->importStateMachine->can($conflictImport, ImportTransitions::RESET)) {
                $this->logImportInfo($conflictImport, " reset to ".ImportState::IDENTITY_CHECKING);
                $this->importStateMachine->apply($conflictImport, ImportTransitions::RESET);
            } else {
                $this->logImportTransitionConstraints($this->importStateMachine, $conflictImport, ImportTransitions::RESET);
            }

        }
        $this->em->flush();
    }

    /**
     * @param ImportQueue $item
     * @param Import      $import
     */
    public function finishCreationQueue(ImportQueue $item, Import $import): void
    {
        if (ImportQueueState::TO_CREATE !== $item->getState()) {
            throw new InvalidArgumentException("Wrong ImportQueue creation state: ".$item->getState());
        }

        $createdHousehold = $this->householdService->create(
            $this->householdDecoratorBuilder->buildHouseholdInputType($item)
        );

        /** @var ImportHouseholdDuplicity $acceptedDuplicity */
        $acceptedDuplicity = $item->getAcceptedDuplicity();
        if (null !== $acceptedDuplicity) {
            $this->linkHouseholdToQueue($import, $createdHousehold, $acceptedDuplicity->getDecideBy());
        } else {
            $this->linkHouseholdToQueue($import, $createdHousehold, $import->getCreatedBy());
        }
        $this->logImportInfo($import, "Created Household #{$createdHousehold->getId()}");

        $this->importQueueStateMachine->apply($item, ImportQueueTransitions::CREATE);
    }

    /**
     * @param ImportQueue $item
     * @param Import      $import
     *
     * @throws EntityNotFoundException|\Exception
     */
    public function finishUpdateQueue(ImportQueue $item, Import $import): void
    {
        if (ImportQueueState::TO_UPDATE !== $item->getState()) {
            throw new InvalidArgumentException("Wrong ImportQueue state");
        }

        /** @var ImportHouseholdDuplicity $acceptedDuplicity */
        $acceptedDuplicity = $item->getAcceptedDuplicity();
        if (null == $acceptedDuplicity) {
            return;
        }

        $householdUpdateInputType = $this->householdDecoratorBuilder->buildHouseholdUpdateType($item);

        $updatedHousehold = $acceptedDuplicity->getTheirs();
        $projects = array_map(function (Project $project) {
            return $project->getId();
        }, $updatedHousehold->getProjects()->toArray());

        foreach ($import->getProjects() as $project) {
            $projects[] = $project->getId();
        }

        $householdUpdateInputType->setProjectIds($projects);

        $updatedHousehold = $acceptedDuplicity->getTheirs();
        $this->householdService->update($updatedHousehold, $householdUpdateInputType);

        $this->linkHouseholdToQueue($import, $updatedHousehold, $acceptedDuplicity->getDecideBy());
        $this->logImportInfo($import, "Updated Household #{$updatedHousehold->getId()}");

        $this->importQueueStateMachine->apply($item, ImportQueueTransitions::UPDATE);
    }

    public function finishLinkQueue(ImportQueue $item, Import $import): void
    {
        /** @var ImportHouseholdDuplicity $acceptedDuplicity */
        $acceptedDuplicity = $item->getAcceptedDuplicity();
        if (null == $acceptedDuplicity) {
            return;
        }

        $this->linkHouseholdToQueue($import, $acceptedDuplicity->getTheirs(), $acceptedDuplicity->getDecideBy());
        $this->logImportInfo($import, "Found old version of Household #{$acceptedDuplicity->getTheirs()->getId()}");

        $this->importQueueStateMachine->apply($item, ImportQueueTransitions::LINK);
    }

    public function finishIgnoreQueue(ImportQueue $item, Import $import): void
    {
        $this->importQueueStateMachine->apply($item, ImportQueueTransitions::IGNORE);
    }

    private function linkHouseholdToQueue(Import $import, Household $household, User $decide): void
    {
        foreach ($household->getBeneficiaries() as $beneficiary) {
            $beneficiaryInImport = new ImportBeneficiary($import, $beneficiary, $decide);
            $this->em->persist($beneficiaryInImport);
        }

        foreach ($import->getProjects() as $project) {
            $household->addProject($project);
        }
        $this->em->persist($household);
    }
}
