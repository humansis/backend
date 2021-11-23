<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use BeneficiaryBundle\Utils\HouseholdService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use NewApiBundle\Component\Import\ValueObject\ImportStatisticsValueObject;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Entity\ImportFile;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportDuplicityState;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\InputType\DuplicityResolveInputType;
use NewApiBundle\InputType\ImportCreateInputType;
use NewApiBundle\InputType\ImportPatchInputType;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Workflow\Exception\WorkflowException;
use NewApiBundle\Workflow\ImportTransitions;
use NewApiBundle\Workflow\WorkflowTool;
use ProjectBundle\Entity\Project;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Workflow\WorkflowInterface;
use UserBundle\Entity\User;

class ImportService
{
    const ASAP_LIMIT = 1000;

    use ImportLoggerTrait;

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var HouseholdService */
    private $householdService;

    /** @var IntegrityChecker */
    private $integrityChecker;

    /** @var ImportInvalidFileService */
    private $importInvalidFileService;

    /** @var IdentityChecker */
    private $identityChecker;

    /** @var SimilarityChecker */
    private $similarityChecker;

    /** @var WorkflowInterface */
    private $importStateMachine;

    /** @var WorkflowInterface */
    private $importQueueStateMachine;

    public function __construct(
        EntityManagerInterface $em,
        HouseholdService $householdService,
        LoggerInterface $importLogger,
        IntegrityChecker $integrityChecker,
        ImportInvalidFileService $importInvalidFileService,
        IdentityChecker $identityChecker,
        SimilarityChecker $similarityChecker,
        WorkflowInterface $importStateMachine,
        WorkflowInterface $importQueueStateMachine
    )
    {
        $this->em = $em;
        $this->householdService = $householdService;
        $this->logger = $importLogger;
        $this->integrityChecker = $integrityChecker;
        $this->importInvalidFileService = $importInvalidFileService;
        $this->identityChecker = $identityChecker;
        $this->similarityChecker = $similarityChecker;
        $this->importStateMachine = $importStateMachine;
        $this->importQueueStateMachine = $importQueueStateMachine;
    }

    public function create(ImportCreateInputType $inputType, User $user): Import
    {
        $project = $this->em->getRepository(Project::class)->find($inputType->getProjectId());

        if (!$project instanceof Project) {
            throw new InvalidArgumentException('Project with ID '.$inputType->getProjectId().' not found');
        }

        $import = new Import(
            $inputType->getTitle(),
            $inputType->getDescription(),
            $project,
            $user,
        );

        $this->em->persist($import);
        $this->em->flush();

        $this->logImportInfo($import, "Was created");

        return $import;
    }

    public function patch(Import $import, ImportPatchInputType $inputType): void
    {
        if (!is_null($inputType->getDescription())) {
            $import->setNotes($inputType->getDescription());
        }

        if (!is_null($inputType->getStatus())) {
            $this->updateStatus($import, $inputType->getStatus());
        }

        if (!is_null($inputType->getTitle())) {
            $import->setTitle($inputType->getTitle());
        }

        $this->em->flush();
    }

    public function updateStatus(Import $import, string $status): void
    {
        $before = $import->getState();
        if($this->importStateMachine->can($import, $status)){
            $this->importStateMachine->apply($import, $status);
            $this->logImportInfo($import, "Changed state from '$before' to '{$import->getState()}'");
            $this->em->flush();
        }else{
            throw new BadRequestHttpException("You can't change state from '$before' to '$status'.");
        }
    }

    public function removeFile(ImportFile $importFile)
    {
        $this->em->remove($importFile);
        $this->em->flush();

        $this->logImportInfo($importFile->getImport(), "Removed file '{$importFile->getFilename()}'");
    }

    public function getStatistics(Import $import): ImportStatisticsValueObject
    {
        $statistics = new ImportStatisticsValueObject();

        /** @var ImportQueueRepository $repository */
        $repository = $this->em->getRepository(ImportQueue::class);

        $statistics->setTotalEntries($import->getImportQueue()->count());
        $statistics->setAmountIntegrityCorrect($repository->getTotalByImportAndStatus($import, ImportQueueState::VALID));
        $statistics->setAmountIntegrityFailed($repository->getTotalByImportAndStatus($import, ImportQueueState::INVALID));
        $statistics->setAmountDuplicities($repository->getTotalByImportAndStatus($import, ImportQueueState::SUSPICIOUS));
        $statistics->setAmountDuplicitiesResolved($repository->getTotalReadyForSave($import));
        $statistics->setAmountEntriesToImport($repository->getTotalReadyForSave($import));
        $statistics->setStatus($import->getState());

        return $statistics;
    }

    public function resolveDuplicity(ImportQueue $importQueue, DuplicityResolveInputType $inputType, User $user)
    {
        //TODO find transition by $inputType->getStatus() (now end status = transition name)
        if ($this->importQueueStateMachine->can($importQueue, $inputType->getStatus())) {
            $this->importQueueStateMachine->apply($importQueue, $inputType->getStatus());
            $importQueue->setState($inputType->getStatus());
        } else {
            throw new WorkflowException('Import Queue is not in valid state.');
        }

        /** @var ImportBeneficiaryDuplicity[] $duplicities */
        $duplicities = $this->em->getRepository(ImportBeneficiaryDuplicity::class)->findBy([
            'ours' => $importQueue,
        ]);

        $updates = [];
        $links = [];
        $uniques = [];
        foreach ($duplicities as $duplicity) {
            if ($duplicity->getId() === $inputType->getAcceptedDuplicityId()) {

                switch ($inputType->getStatus()) {
                    case ImportQueueState::TO_UPDATE:
                        $duplicity->setState(ImportDuplicityState::DUPLICITY_KEEP_OURS);
                        $updates[] = '#'.$duplicity->getId();
                        break;
                    case ImportQueueState::TO_LINK:
                        $duplicity->setState(ImportDuplicityState::DUPLICITY_KEEP_THEIRS);
                        $links[] = '#'.$duplicity->getId();
                        break;
                }

            } else {
                $duplicity->setState(ImportDuplicityState::NO_DUPLICITY);
                $uniques[] = '#'.$duplicity->getId();
            }

            $duplicity->setDecideBy($user);
            $duplicity->setDecideAt(new DateTime());
        }
        if (!empty($updates)) {
            $this->logImportInfo($importQueue->getImport(), "[Queue #{$importQueue->getId()}] Duplicity suspect(s) [".implode(', ', $updates)."] was resolved as more current duplicity");
        } else {
            $this->logImportDebug($importQueue->getImport(), "[Queue #{$importQueue->getId()}] Nothing was resolved as more current duplicity");
        }
        if (!empty($links)) {
            $this->logImportInfo($importQueue->getImport(), "[Queue #{$importQueue->getId()}] Duplicity suspect(s) [".implode(', ', $updates)."] was resolved as older duplicity");
        } else {
            $this->logImportDebug($importQueue->getImport(), "[Queue #{$importQueue->getId()}] Nothing was resolved as older duplicity");
        }
        if (!empty($uniques)) {
            $this->logImportInfo($importQueue->getImport(), "[Queue #{$importQueue->getId()}] Duplicity suspect(s) [".implode(', ', $updates)."] was resolved as mistake and will be inserted");
        } else {
            $this->logImportDebug($importQueue->getImport(), "[Queue #{$importQueue->getId()}] Nothing was resolved as mistake");
        }

        $import = $importQueue->getImport();

        $this->em->flush();

        switch ($import->getState()) {
            case ImportState::IDENTITY_CHECK_FAILED:
                if (!$this->identityChecker->isImportQueueSuspicious($import)) {
                    $importTransition = ImportTransitions::COMPLETE_IDENTITY;
                    $import->setState(ImportState::IDENTITY_CHECK_CORRECT);
                }
                break;
            case ImportState::SIMILARITY_CHECK_FAILED:
                if (!$this->similarityChecker->isImportQueueSuspicious($import)) {
                    $importTransition = ImportTransitions::COMPLETE_SIMILARITY;
                    $import->setState(ImportState::SIMILARITY_CHECK_CORRECT);
                }
        }

        if (isset($importTransition)) {
            if ($this->importStateMachine->can($import, $importTransition)) {
                $this->importStateMachine->apply($import, $importTransition);
            } else {
                throw new WorkflowException('Import is in invalid state');
            }
        }

        $this->em->flush();
    }

    /**
     * @param Import   $import
     * @param int|null $batchSize if null => all
     */
    public function checkIntegrity(Import $import, ?int $batchSize = null): void
    {
        WorkflowTool::checkAndApply($this->importStateMachine, $import,
            [ImportTransitions::REDO_INTEGRITY, ImportTransitions::FAIL_INTEGRITY, ImportTransitions::COMPLETE_INTEGRITY]);
    }

    /**
     * @param Import   $import
     * @param int|null $batchSize if null => all
     */
    public function checkIdentity(Import $import, ?int $batchSize = null): void
    {
        WorkflowTool::checkAndApply($this->importStateMachine, $import,
            [ImportTransitions::REDO_IDENTITY, ImportTransitions::FAIL_IDENTITY, ImportTransitions::COMPLETE_IDENTITY]);
    }

    /**
     * @param Import   $import
     * @param int|null $batchSize if null => all
     */
    public function checkSimilarity(Import $import, ?int $batchSize = null): void
    {
        WorkflowTool::checkAndApply($this->importStateMachine, $import,
            [ImportTransitions::REDO_SIMILARITY, ImportTransitions::FAIL_SIMILARITY, ImportTransitions::COMPLETE_SIMILARITY]);
    }

    public function finish(Import $import): void
    {
        WorkflowTool::checkAndApply($this->importStateMachine, $import, [ImportTransitions::FINISH]);
    }

    private function removeFinishedQueue(ImportQueue $queue): void
    {
        foreach ($queue->getDuplicities() as $duplicity) {
            $this->em->remove($duplicity);
        }
        $this->em->remove($queue);
    }


}

