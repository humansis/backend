<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use BadMethodCallException;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Utils\HouseholdService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ObjectRepository;
use InvalidArgumentException;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportBeneficiary;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Workflow\ImportQueueTransitions;
use NewApiBundle\Workflow\ImportTransitions;
use NewApiBundle\Workflow\WorkflowTool;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use UserBundle\Entity\User;

class ImportFinisher
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
     * @var HouseholdService
     */
    private $householdService;

    /**
     * @var ObjectRepository|ImportQueueRepository
     */
    private $queueRepository;

    public function __construct(
        EntityManagerInterface $em,
        HouseholdService       $householdService,
        LoggerInterface        $logger,
        WorkflowInterface      $importStateMachine,
        WorkflowInterface      $importQueueStateMachine
    ) {
        $this->em = $em;
        $this->importStateMachine = $importStateMachine;
        $this->importQueueStateMachine = $importQueueStateMachine;
        $this->householdService = $householdService;
        $this->queueRepository = $em->getRepository(ImportQueue::class);
        $this->logger = $logger;
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

        $queueToInsert = $this->queueRepository->findBy([
            'import' => $import,
            'state' => ImportQueueState::TO_CREATE,
        ]);
        $this->logImportDebug($import, "Items to save: ".count($queueToInsert));
        foreach ($queueToInsert as $item) {
            $this->finishCreationQueue($item, $import);
            $this->em->persist($item);
        }

        $queueToUpdate = $this->queueRepository->findBy([
            'import' => $import,
            'state' => ImportQueueState::TO_UPDATE,
        ]);
        $this->logImportDebug($import, "Items to update: ".count($queueToUpdate));
        foreach ($queueToUpdate as $item) {
            $this->finishUpdateQueue($item, $import);
            $this->em->persist($item);
        }

        // will be removed in clean command
        /*foreach ($this->queueRepository->findBy([
            'import' => $import,
            'state' => ImportQueueState::TO_IGNORE,
        ]) as $item) {
            $this->removeFinishedQueue($item);
        }*/

        // TODO TO_IGNORE = TO_LINK => unify states in the future
        $queueToLink = $this->queueRepository->findBy([
            'import' => $import,
            'state' => [ImportQueueState::TO_LINK, ImportQueueState::TO_IGNORE],
        ]);
        $this->logImportDebug($import, "Items to link: ".count($queueToLink));
        foreach ($queueToLink as $item) {
            /** @var ImportBeneficiaryDuplicity $acceptedDuplicity */
            $acceptedDuplicity = $item->getAcceptedDuplicity();
            if (null == $acceptedDuplicity) {
                continue;
            }

            $this->linkHouseholdToQueue($import, $acceptedDuplicity->getTheirs(), $acceptedDuplicity->getDecideBy());
            $this->logImportInfo($import, "Found old version of Household #{$acceptedDuplicity->getTheirs()->getId()}");

            WorkflowTool::checkAndApply($this->importQueueStateMachine, $item, [ImportQueueTransitions::LINK]);
            $this->em->persist($item);
        }

        $this->em->flush();

        WorkflowTool::checkAndApply($this->importStateMachine, $import, [ImportTransitions::FINISH]);
        $this->em->persist($import);
        $this->em->flush();
    }

    /**
     * @param Import $import
     */
    public function finish(Import $import)
    {
        if ($import->getState() !== ImportState::FINISHED) {
            throw new BadMethodCallException('Wrong import status');
        }

        $importConflicts = $this->em->getRepository(Import::class)->getConflictingImports($import);
        $this->logImportInfo($import, count($importConflicts)." conflicting imports to reset duplicity checks");
        foreach ($importConflicts as $conflictImport) {
            $this->logImportInfo($conflictImport, " reset to ".ImportState::IDENTITY_CHECKING);
            $this->importStateMachine->apply($conflictImport, ImportTransitions::RESET);
        }
        $this->em->flush();
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
        $this->logImportInfo($import, "Created Household #{$createdHousehold->getId()}");

        WorkflowTool::checkAndApply($this->importQueueStateMachine, $item, [ImportQueueTransitions::CREATE]);
    }

    /**
     * @param ImportQueue $item
     * @param Import      $import
     *
     * @throws EntityNotFoundException
     */
    private function finishUpdateQueue(ImportQueue $item, Import $import): void
    {
        if (ImportQueueState::TO_UPDATE !== $item->getState()) {
            throw new InvalidArgumentException("Wrong ImportQueue state");
        }

        /** @var ImportBeneficiaryDuplicity $acceptedDuplicity */
        $acceptedDuplicity = $item->getAcceptedDuplicity();
        if (null == $acceptedDuplicity) {
            return;
        }

        $headContent = $item->getContent()[0];
        $memberContents = array_slice($item->getContent(), 1);
        $hhh = new Integrity\HouseholdHead((array) $headContent, $import->getProject()->getIso3(), $this->em);
        $householdUpdateInputType = $hhh->buildHouseholdUpdateType();
        $householdUpdateInputType->setProjectIds([$import->getProject()->getId()]);

        foreach ($memberContents as $memberContent) {
            $hhm = new Integrity\HouseholdMember($memberContent, $import->getProject()->getIso3(), $this->em);
            $householdUpdateInputType->addBeneficiary($hhm->buildBeneficiaryInputType());
        }

        $updatedHousehold = $acceptedDuplicity->getTheirs();
        $this->householdService->update($updatedHousehold, $householdUpdateInputType);

        $this->linkHouseholdToQueue($import, $updatedHousehold, $acceptedDuplicity->getDecideBy());
        $this->logImportInfo($import, "Updated Household #{$updatedHousehold->getId()}");

        WorkflowTool::checkAndApply($this->importQueueStateMachine, $item, [ImportQueueTransitions::UPDATE]);
    }

    private function linkHouseholdToQueue(Import $import, Household $household, User $decide): void
    {
        foreach ($household->getBeneficiaries() as $beneficiary) {
            $beneficiaryInImport = new ImportBeneficiary($import, $beneficiary, $decide);
            $this->em->persist($beneficiaryInImport);
        }
    }
}
