<?php

declare(strict_types=1);

namespace Component\Import;

use Entity\Project;
use Utils\HouseholdService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use InvalidArgumentException;
use Component\Import\Integrity;
use Component\Import\ValueObject\ImportStatisticsValueObject;
use Entity;
use Enum\ImportQueueState;
use Enum\ImportState;
use InputType\Import;
use Repository\ImportBeneficiaryDuplicityRepository;
use Repository\ImportQueueRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Workflow\TransitionBlocker;
use Symfony\Component\Workflow\WorkflowInterface;
use Entity\User;
use Workflow\ImportTransitions;

class ImportService
{
    use ImportLoggerTrait;

    public const ASAP_LIMIT = 100;

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

    /** @var DuplicityResolver */
    private $duplicityResolver;

    /** @var Integrity\DuplicityService */
    private $integrityDuplicityService;

    public function __construct(
        EntityManagerInterface $em,
        HouseholdService $householdService,
        LoggerInterface $importLogger,
        IntegrityChecker $integrityChecker,
        ImportInvalidFileService $importInvalidFileService,
        IdentityChecker $identityChecker,
        SimilarityChecker $similarityChecker,
        WorkflowInterface $importStateMachine,
        WorkflowInterface $importQueueStateMachine,
        DuplicityResolver $duplicityResolver,
        Integrity\DuplicityService $integrityDuplicityService
    ) {
        $this->em = $em;
        $this->householdService = $householdService;
        $this->logger = $importLogger;
        $this->integrityChecker = $integrityChecker;
        $this->importInvalidFileService = $importInvalidFileService;
        $this->identityChecker = $identityChecker;
        $this->similarityChecker = $similarityChecker;
        $this->importStateMachine = $importStateMachine;
        $this->importQueueStateMachine = $importQueueStateMachine;
        $this->duplicityResolver = $duplicityResolver;
        $this->integrityDuplicityService = $integrityDuplicityService;
    }

    public function create(string $countryIso3, Import\CreateInputType $inputType, User $user): Entity\Import
    {
        if (empty($inputType->getProjects())) {
            $project = $this->em->getRepository(Project::class)->find($inputType->getProjectId());

            if (!$project instanceof Project) {
                throw new BadRequestHttpException('Project with ID ' . $inputType->getProjectId() . ' not found');
            }
            if ($project->getCountryIso3() !== $countryIso3) {
                throw new BadRequestHttpException("Project is in {$project->getCountryIso3()} but you works in $countryIso3");
            }

            $projects = [$project];
        } else {
            $projects = $this->em->getRepository(Project::class)->findBy(['id' => $inputType->getProjects()]);

            if (count($projects) < count($inputType->getProjects())) {
                throw new InvalidArgumentException('Some Project ID not found');
            }
        }

        $import = new Entity\Import(
            $countryIso3,
            $inputType->getTitle(),
            $inputType->getDescription(),
            $projects,
            $user,
        );

        $this->em->persist($import);
        $this->em->flush();

        $this->logImportInfo($import, "Was created");

        return $import;
    }

    public function patch(Entity\Import $import, Import\PatchInputType $inputType): void
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

    public function updateStatus(Entity\Import $import, string $status): void
    {
        $before = $import->getState();
        if ($this->importStateMachine->can($import, $status)) {
            $this->importStateMachine->apply($import, $status);
            $this->logImportInfo($import, "Changed state from '$before' to '{$import->getState()}'");
            $this->em->flush();
        } else {
            $this->logImportTransitionConstraints($this->importStateMachine, $import, $status);
            $reasons = [];
            foreach ($this->importStateMachine->buildTransitionBlockerList($import, $status)->getIterator() as $reason) {
                /**
                 * @var $reason TransitionBlocker
                 */
                $reasons[] = $reason->getMessage();
            }

            throw new BadRequestHttpException(join(',', $reasons));
        }
    }

    public function removeFile(Entity\ImportFile $importFile)
    {
        $import = $importFile->getImport();
        $this->em->remove($importFile);
        $this->em->flush();

        $this->integrityDuplicityService->buildIdentityTable($import);

        $this->logImportInfo($importFile->getImport(), "Removed file '{$importFile->getFilename()}'");
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getStatistics(Entity\Import $import): ImportStatisticsValueObject
    {
        $statistics = new ImportStatisticsValueObject();

        /** @var ImportQueueRepository $importQueueRepository */
        $importQueueRepository = $this->em->getRepository(Entity\ImportQueue::class);

        /** @var ImportBeneficiaryDuplicityRepository $importBeneficiaryDuplicityRepository */
        $importBeneficiaryDuplicityRepository = $this->em->getRepository(Entity\ImportBeneficiaryDuplicity::class);

        $statistics->setTotalEntries($importQueueRepository->count(['import' => $import]));
        $statistics->setAmountIntegrityCorrect($importQueueRepository->getTotalByImportAndStatus($import, ImportQueueState::VALID));
        $statistics->setAmountIntegrityFailed($importQueueRepository->getTotalByImportAndStatuses($import, [ImportQueueState::INVALID, ImportQueueState::INVALID_EXPORTED]));
        $statistics->setAmountIdentityDuplicities($importBeneficiaryDuplicityRepository->getTotalByImport($import));
        $statistics->setAmountIdentityDuplicitiesResolved($importQueueRepository->getTotalResolvedDuplicities($import));
        $statistics->setAmountEntriesToImport($importQueueRepository->getTotalReadyForSave($import));
        $statistics->setStatus($import->getState());

        return $statistics;
    }

    public function resolveDuplicity(Entity\ImportQueue $importQueue, Import\Duplicity\ResolveSingleDuplicityInputType $inputType, User $user)
    {
        $this->logImportInfo($importQueue->getImport(), "[Queue#{$importQueue->getId()}] decided as " . $inputType->getStatus());
        if ($this->importQueueStateMachine->can($importQueue, $inputType->getStatus())) {
            $this->duplicityResolver->resolve($importQueue, $inputType->getAcceptedDuplicityId(), $inputType->getStatus(), $user);

            $this->em->flush();

            if ($this->importStateMachine->can($importQueue->getImport(), ImportTransitions::RESOLVE_IDENTITY_DUPLICITIES)) {
                $this->importStateMachine->apply($importQueue->getImport(), ImportTransitions::RESOLVE_IDENTITY_DUPLICITIES);
            }
        } else {
            foreach ($this->importQueueStateMachine->buildTransitionBlockerList($importQueue, $inputType->getStatus()) as $block) {
                $this->logImportInfo($importQueue->getImport(), "[Queue#{$importQueue->getId()}] can't go '{$inputType->getStatus()}' because " . $block->getMessage());
            }
            throw new BadRequestHttpException("You can't resolve duplicity. Import Queue is not in valid state.");
        }
    }

    public function resolveAllDuplicities(Entity\Import $import, Import\Duplicity\ResolveAllDuplicitiesInputType $inputType, User $user)
    {
        if (
            !in_array($import->getState(), [
                ImportState::IDENTITY_CHECK_FAILED,
                ImportState::IDENTITY_CHECK_CORRECT,
                ImportState::SIMILARITY_CHECK_FAILED,
                ImportState::SIMILARITY_CHECK_CORRECT,
            ])
        ) {
            throw new BadRequestHttpException("You can't resolve all duplicities. Import is not in valid state.");
        }
        $singleDuplicityQueues = $this->em->getRepository(Entity\ImportQueue::class)->findSingleDuplicityQueues($import);

        /** @var Entity\ImportQueue $importQueue */
        foreach ($singleDuplicityQueues as $importQueue) {
            $duplicities = $importQueue->getHouseholdDuplicities();
            if ($duplicities->count() !== 1) {
                // this is only for paranoid measures
                $this->logImportError($import, "[Queue#{$importQueue->getId()}] has no or more duplicity candidates that 1");
                continue;
            }

            /** @var Entity\ImportHouseholdDuplicity $duplicity */
            $duplicity = $duplicities[0];
            if ($this->importQueueStateMachine->can($importQueue, $inputType->getStatus())) {
                $this->duplicityResolver->resolve($importQueue, $duplicity->getTheirs()->getId(), $inputType->getStatus(), $user);
            } else {
                foreach ($this->importQueueStateMachine->buildTransitionBlockerList($importQueue, $inputType->getStatus()) as $block) {
                    $this->logImportInfo($importQueue->getImport(), "[Queue#{$importQueue->getId()}] can't go '{$inputType->getStatus()}' because " . $block->getMessage());
                }
                throw new BadRequestHttpException("You can't resolve duplicity. Import Queue is not in valid state.");
            }
        }

        $this->em->flush();

        if ($this->importStateMachine->can($import, ImportTransitions::RESOLVE_IDENTITY_DUPLICITIES)) {
            $this->importStateMachine->apply($import, ImportTransitions::RESOLVE_IDENTITY_DUPLICITIES);
        }

        $this->logImportInfo($import, "All items was decided as " . $inputType->getStatus());
    }

    private function removeFinishedQueue(Entity\ImportQueue $queue): void
    {
        foreach ($queue->getHouseholdDuplicities() as $duplicity) {
            $this->em->remove($duplicity);
        }
        $this->em->remove($queue);
    }
}
