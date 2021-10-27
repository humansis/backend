<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Utils\HouseholdService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use NewApiBundle\Component\Import\ValueObject\ImportStatisticsValueObject;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportBeneficiary;
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
use NewApiBundle\Workflow\ImportQueueTransitions;
use NewApiBundle\Workflow\ImportTransitions;
use ProjectBundle\Entity\Project;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Workflow\Exception\LogicException;
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

            if (count($import->getImportQueue()) < self::ASAP_LIMIT) {
                $this->em->flush();
                $this->logImportInfo($import, "Because of small import, it will be processed immediately");

                switch ($import->getState()) {
                    case ImportState::INTEGRITY_CHECKING:
                        $this->checkIntegrity($import);
                        break;
                    case ImportState::IDENTITY_CHECKING:
                        $this->checkIdentity($import);
                        break;
                    case ImportState::SIMILARITY_CHECKING:
                        $this->checkSimilarity($import);
                        break;
                    case ImportState::IMPORTING:
                        $this->finish($import);
                        break;
                }
            }
        }

        if (!is_null($inputType->getTitle())) {
            $import->setTitle($inputType->getTitle());
        }

        $this->em->flush();
    }

    public function updateStatus(Import $import, string $status): void
    {
        $before = $import->getState();
        try {
            $this->importStateMachine->apply($import, $status);
            $import->setState($status);
            $this->logImportInfo($import, "Changed state from '$before' to '{$import->getState()}'");
            $this->em->flush();
        } catch (LogicException $exception) {
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
            if ($this->importStateMachine->can($import, ImportTransitions::COMPLETE_IDENTITY)) {
                $this->importStateMachine->apply($import, ImportTransitions::COMPLETE_IDENTITY);
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
        // fail if there is no valid file
        if (0 === $this->em->getRepository(ImportFile::class)->count([
                'import' => $import,
                'structureViolations' => null,
            ])) {

            if ($this->importStateMachine->can($import, ImportTransitions::FAIL_INTEGRITY)) {
                $this->importStateMachine->apply($import, ImportTransitions::FAIL_INTEGRITY);
                $import->setState(ImportState::INTEGRITY_CHECK_FAILED);
                $this->em->persist($import);
                $this->em->flush();

                return;
            } else {
                throw new WorkflowException();
            }
        }
        $this->integrityChecker->check($import, $batchSize);

        // moved to ImportWorkflowSubscriber
        // if (ImportState::INTEGRITY_CHECK_FAILED === $import->getState()) {
        //     $this->importInvalidFileService->generateFile($import);
        // }
    }

    /**
     * @param Import   $import
     * @param int|null $batchSize if null => all
     */
    public function checkIdentity(Import $import, ?int $batchSize = null): void
    {
        // fail if there is no valid queue items
        if (0 === $this->em->getRepository(ImportQueue::class)->count([
                'import' => $import,
                'state' => ImportQueueState::VALID,
            ])) {

            if ($this->importStateMachine->can($import, ImportTransitions::FAIL_IDENTITY)) {
                $this->importStateMachine->apply($import, ImportTransitions::FAIL_IDENTITY);
                $import->setState(ImportState::IDENTITY_CHECK_FAILED);
                $this->em->persist($import);
                $this->em->flush();

                return;
            } else {
                throw new WorkflowException();
            }
        }
        $this->identityChecker->check($import, $batchSize);
    }

    /**
     * @param Import   $import
     * @param int|null $batchSize if null => all
     */
    public function checkSimilarity(Import $import, ?int $batchSize = null): void
    {
        $this->similarityChecker->check($import, $batchSize);
    }

    public function finish(Import $import): void
    {
        if ($import->getState() !== ImportState::IMPORTING) {
            throw new WorkflowException('Wrong import status');
        }

        $queueRepo = $this->em->getRepository(ImportQueue::class);

        $queueToInsert = $queueRepo->findBy([
            'import' => $import,
            'state' => ImportQueueState::TO_CREATE,
        ]);
        $this->logImportDebug($import, "Items to save: ".count($queueToInsert));
        foreach ($queueToInsert as $item) {
            $this->finishCreationQueue($item, $import);
        }

        $queueToUpdate = $queueRepo->findBy([
            'import' => $import,
            'state' => ImportQueueState::TO_UPDATE,
        ]);
        $this->logImportDebug($import, "Items to update: ".count($queueToUpdate));
        foreach ($queueToUpdate as $item) {
            $this->finishUpdateQueue($item, $import);
        }

        // will be removed in clean command
        /*foreach ($queueRepo->findBy([
            'import' => $import,
            'state' => ImportQueueState::TO_IGNORE,
        ]) as $item) {
            $this->removeFinishedQueue($item);
        }*/

        $queueToLink = $queueRepo->findBy([
            'import' => $import,
            'state' => ImportQueueState::TO_LINK,
        ]);
        $this->logImportDebug($import, "Items to link: ".count($queueToLink));
        foreach ($queueToLink as $item) {
            /** @var ImportBeneficiaryDuplicity $acceptedDuplicity */
            $acceptedDuplicity = $item->getAcceptedDuplicity();
            if (null == $acceptedDuplicity) continue;

            $this->linkHouseholdToQueue($import, $acceptedDuplicity->getTheirs(), $acceptedDuplicity->getDecideBy());
            //$this->removeFinishedQueue($item);
            $this->logImportInfo($import, "Found old version of Household #{$acceptedDuplicity->getTheirs()->getId()}");
        }

        // will be removed in clean command
        /*foreach ($queueRepo->findBy([
            'import' => $import,
            'state' => ImportQueueState::INVALID_EXPORTED,
        ]) as $item) {
            $this->removeFinishedQueue($item);
        }*/

        if($this->importStateMachine->can($import, ImportTransitions::FINISH)){
            $this->importStateMachine->apply($import, ImportState::FINISHED);
            $import->setState(ImportState::FINISHED);
            $this->em->persist($import);
        }
        $this->em->flush();

        $importConflicts = $this->em->getRepository(Import::class)->getConflictingImports($import);
        $this->logImportInfo($import, count($importConflicts)." conflicting imports to reset duplicity checks");
        foreach ($importConflicts as $conflictImport) {
            if ($this->importStateMachine->can($conflictImport, ImportTransitions::CHECK_IDENTITY)) {
                $conflictImport->setState(ImportState::IDENTITY_CHECKING);
                $conflictQueue = $queueRepo->findBy([
                    'import' => $conflictImport,
                ]);
                foreach ($conflictQueue as $item) {
                    if ($this->importQueueStateMachine->can($item, ImportQueueTransitions::VALIDATE)) {
                        $item->setState(ImportQueueState::VALID);
                        $item->setIdentityCheckedAt(null);
                        $item->setSimilarityCheckedAt(null);
                        $this->em->persist($item);
                    }
                }
                $this->em->persist($conflictImport);
                $this->em->flush();
                $this->logImportInfo($conflictImport,
                    "Duplicity checks of ".count($conflictQueue)." queue items reset because finish Import #{$import->getId()} ({$import->getTitle()})");
            }
        }
    }

    private function linkHouseholdToQueue(Import $import, Household $household, User $decide): void
    {
        foreach ($household->getBeneficiaries() as $beneficiary) {
            $beneficiaryInImport = new ImportBeneficiary($import, $beneficiary, $decide);
            $this->em->persist($beneficiaryInImport);
        }
    }

    private function removeFinishedQueue(ImportQueue $queue): void
    {
        foreach ($queue->getDuplicities() as $duplicity) {
            $this->em->remove($duplicity);
        }
        $this->em->remove($queue);
    }

    /**
     * @param ImportQueue $item
     * @param Import      $import
     */
    private function finishCreationQueue(ImportQueue $item, Import $import): void
    {
        if (ImportQueueState::TO_CREATE !== $item->getState()) {
            throw new InvalidArgumentException("Wrong ImportQueue creation state: ".$item->getState());
        }

        $headContent = $item->getContent()[0];
        $memberContents = array_slice($item->getContent(), 1);
        $hhh = new Integrity\HouseholdHead((array) $headContent, $import->getProject()->getIso3(), $this->em);
        $householdCreateInputType = $hhh->buildHouseholdInputType();
        $householdCreateInputType->setProjectIds([$import->getProject()->getId()]);

        foreach ($memberContents as $memberContent) {
            $hhm = new Integrity\HouseholdMember($memberContent, $import->getProject()->getIso3(), $this->em);
            $householdCreateInputType->addBeneficiary($hhm->buildBeneficiaryInputType());
        }

        $createdHousehold = $this->householdService->create($householdCreateInputType);

        /** @var ImportBeneficiaryDuplicity $acceptedDuplicity */
        $acceptedDuplicity = $item->getAcceptedDuplicity();
        if (null !== $acceptedDuplicity) {
            $this->linkHouseholdToQueue($import, $createdHousehold, $acceptedDuplicity->getDecideBy());
        } else {
            $this->linkHouseholdToQueue($import, $createdHousehold, $import->getCreatedBy());
        }
        //$this->removeFinishedQueue($item);
        $this->logImportInfo($import, "Created Household #{$createdHousehold->getId()}");
    }

    /**
     * @param ImportQueue $item
     * @param Import      $import
     */
    private function finishUpdateQueue(ImportQueue $item, Import $import): void
    {
        if (ImportQueueState::TO_UPDATE !== $item->getState()) {
            throw new InvalidArgumentException("Wrong ImportQueue state");
        }

        /** @var ImportBeneficiaryDuplicity $acceptedDuplicity */
        $acceptedDuplicity = $item->getAcceptedDuplicity();
        if (null == $acceptedDuplicity) return;

        $headContent = $item->getContent()[0];
        $memberContents = array_slice($item->getContent(), 1);
        $hhh = new Integrity\HouseholdHead((array)$headContent, $import->getProject()->getIso3(), $this->em);
        $householdUpdateInputType = $hhh->buildHouseholdUpdateType();
        $householdUpdateInputType->setProjectIds([$import->getProject()->getId()]);

        foreach ($memberContents as $memberContent) {
            $hhm = new Integrity\HouseholdMember($memberContent, $import->getProject()->getIso3(), $this->em);
            $householdUpdateInputType->addBeneficiary($hhm->buildBeneficiaryInputType());
        }

        $updatedHousehold = $acceptedDuplicity->getTheirs();
        $this->householdService->update($updatedHousehold, $householdUpdateInputType);

        $this->linkHouseholdToQueue($import, $updatedHousehold, $acceptedDuplicity->getDecideBy());
        //$this->removeFinishedQueue($item);
        $this->logImportInfo($import, "Updated Household #{$updatedHousehold->getId()}");
    }

}

