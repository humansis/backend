<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Service;

use BeneficiaryBundle\Utils\HouseholdService;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use NewApiBundle\Component\Import\Duplicity\DuplicityResolver;
use NewApiBundle\Component\Import\Duplicity\IdentityChecker;
use NewApiBundle\Component\Import\Duplicity\SimilarityChecker;
use NewApiBundle\Component\Import\Integrity\IntegrityChecker;
use NewApiBundle\Component\Import\Utils\ImportLoggerTrait;
use NewApiBundle\Component\Import\ValueObject\ImportStatisticsValueObject;
use NewApiBundle\Component\Import\Entity\Import;
use NewApiBundle\Component\Import\Entity\File;
use NewApiBundle\Component\Import\Entity\Queue;
use NewApiBundle\Component\Import\Enum\QueueState;
use NewApiBundle\Component\Import\Enum\State;
use NewApiBundle\InputType\DuplicityResolveInputType;
use NewApiBundle\InputType\ImportCreateInputType;
use NewApiBundle\InputType\ImportPatchInputType;
use NewApiBundle\Component\Import\Repository\QueueRepository;
use NewApiBundle\Component\Import\Enum\Transitions;
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

    /** @var InvalidFileService */
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

    public function __construct(
        EntityManagerInterface $em,
        HouseholdService $householdService,
        LoggerInterface $importLogger,
        IntegrityChecker $integrityChecker,
        InvalidFileService $importInvalidFileService,
        IdentityChecker $identityChecker,
        SimilarityChecker $similarityChecker,
        WorkflowInterface $importStateMachine,
        WorkflowInterface $importQueueStateMachine,
        DuplicityResolver $duplicityResolver
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
        $this->duplicityResolver = $duplicityResolver;
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
            throw new BadRequestHttpException("You can't do transition '$status' state from '$before'.");
        }
    }

    public function removeFile(File $importFile)
    {
        $this->em->remove($importFile);
        $this->em->flush();

        $this->logImportInfo($importFile->getImport(), "Removed file '{$importFile->getFilename()}'");
    }

    public function getStatistics(Import $import): ImportStatisticsValueObject
    {
        $statistics = new ImportStatisticsValueObject();

        /** @var QueueRepository $repository */
        $repository = $this->em->getRepository(Queue::class);

        $statistics->setTotalEntries($repository->count(['import'=>$import]));
        $statistics->setAmountIntegrityCorrect($repository->getTotalByImportAndStatus($import, QueueState::VALID));
        $statistics->setAmountIntegrityFailed($repository->getTotalByImportAndStatus($import, QueueState::INVALID));
        $statistics->setAmountDuplicities($repository->getTotalByImportAndStatus($import, QueueState::IDENTITY_CANDIDATE));
        $statistics->setAmountDuplicitiesResolved($repository->getTotalReadyForSave($import));
        $statistics->setAmountEntriesToImport($repository->getTotalReadyForSave($import));
        $statistics->setStatus($import->getState());

        return $statistics;
    }

    public function resolveDuplicity(Queue $importQueue, DuplicityResolveInputType $inputType, User $user)
    {
        $this->logImportInfo($importQueue->getImport(), "[Queue#{$importQueue->getId()}] decided as ".$inputType->getStatus());
        if ($this->importQueueStateMachine->can($importQueue, $inputType->getStatus())) {
            $this->duplicityResolver->resolve($importQueue, $inputType->getAcceptedDuplicityId(),$inputType->getStatus(), $user);
            foreach ($this->importQueueStateMachine->buildTransitionBlockerList($importQueue, $inputType->getStatus()) as $block) {
                $this->logImportInfo($importQueue->getImport(), "[Queue#{$importQueue->getId()}] can't go '{$inputType->getStatus()}' because ".$block->getMessage());
            }
            $this->importQueueStateMachine->apply($importQueue, $inputType->getStatus());
            $this->em->flush();

            // check if it is all to decide
            if ($this->importStateMachine->can($importQueue->getImport(), Transitions::RESOLVE_IDENTITY_DUPLICITIES)) {
                $this->importStateMachine->apply($importQueue->getImport(), Transitions::RESOLVE_IDENTITY_DUPLICITIES);
            } elseif ($this->importStateMachine->can($importQueue->getImport(), Transitions::RESOLVE_SIMILARITY_DUPLICITIES)) {
                $this->importStateMachine->apply($importQueue->getImport(), Transitions::RESOLVE_SIMILARITY_DUPLICITIES);
            }

            $this->em->flush();
        } else {
            throw new BadRequestHttpException("You can't resolve duplicity. Import Queue is not in valid state.");
        }
    }

    private function removeFinishedQueue(Queue $queue): void
    {
        foreach ($queue->getDuplicities() as $duplicity) {
            $this->em->remove($duplicity);
        }
        $this->em->remove($queue);
    }


}

