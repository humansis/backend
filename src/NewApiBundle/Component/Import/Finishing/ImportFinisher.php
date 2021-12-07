<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Finishing;

use BadMethodCallException;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Utils\HouseholdService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ObjectRepository;
use InvalidArgumentException;
use NewApiBundle\Component\Import\Entity\Import;
use NewApiBundle\Component\Import\Entity\Beneficiary;
use NewApiBundle\Component\Import\Entity\BeneficiaryDuplicity;
use NewApiBundle\Component\Import\Entity\Queue;
use NewApiBundle\Component\Import\Enum\QueueState;
use NewApiBundle\Component\Import\Enum\State;
use NewApiBundle\Component\Import\Repository\QueueRepository;
use NewApiBundle\Component\Import\Enum\QueueTransitions;
use NewApiBundle\Component\Import\Enum\Transitions;
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
     * @var ObjectRepository|QueueRepository
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
        $this->queueRepository = $em->getRepository(Queue::class);
        $this->logger = $logger;
    }

    /**
     * @param Import $import
     *
     * @throws EntityNotFoundException
     */
    public function import(Import $import)
    {
        if ($import->getState() !== State::IMPORTING) {
            throw new BadMethodCallException('Wrong import status');
        }

        $queueToInsert = $this->queueRepository->findBy([
            'import' => $import,
            'state' => QueueState::TO_CREATE,
        ]);
        $this->logImportDebug($import, "Items to save: ".count($queueToInsert));
        foreach ($queueToInsert as $item) {
            $this->finishCreationQueue($item, $import);
            $this->em->persist($item);
        }

        $queueToUpdate = $this->queueRepository->findBy([
            'import' => $import,
            'state' => QueueState::TO_UPDATE,
        ]);
        $this->logImportDebug($import, "Items to update: ".count($queueToUpdate));
        foreach ($queueToUpdate as $item) {
            $this->finishUpdateQueue($item, $import);
            $this->em->persist($item);
        }

        // will be removed in clean command
        /*foreach ($this->queueRepository->findBy([
            'import' => $import,
            'state' => QueueState::TO_IGNORE,
        ]) as $item) {
            $this->removeFinishedQueue($item);
        }*/

        // TODO TO_IGNORE = TO_LINK => unify states in the future
        $queueToLink = $this->queueRepository->findBy([
            'import' => $import,
            'state' => [QueueState::TO_LINK, QueueState::TO_IGNORE],
        ]);
        $this->logImportDebug($import, "Items to link: ".count($queueToLink));
        foreach ($queueToLink as $item) {
            /** @var BeneficiaryDuplicity $acceptedDuplicity */
            $acceptedDuplicity = $item->getAcceptedDuplicity();
            if (null == $acceptedDuplicity) {
                continue;
            }

            $this->linkHouseholdToQueue($import, $acceptedDuplicity->getTheirs(), $acceptedDuplicity->getDecideBy());
            $this->logImportInfo($import, "Found old version of Household #{$acceptedDuplicity->getTheirs()->getId()}");

            WorkflowTool::checkAndApply($this->importQueueStateMachine, $item, [QueueTransitions::LINK]);
            $this->em->persist($item);
        }

        $this->em->flush();

        WorkflowTool::checkAndApply($this->importStateMachine, $import, [Transitions::FINISH]);
        $this->em->persist($import);
        $this->em->flush();
    }

    /**
     * @param Import $import
     */
    public function finish(Import $import)
    {
        if ($import->getState() !== State::FINISHED) {
            throw new BadMethodCallException('Wrong import status');
        }

        $importConflicts = $this->em->getRepository(Import::class)->getConflictingImports($import);
        $this->logImportInfo($import, count($importConflicts)." conflicting imports to reset duplicity checks");
        foreach ($importConflicts as $conflictImport) {
            $this->logImportInfo($conflictImport, " reset to ".State::IDENTITY_CHECKING);
            $this->importStateMachine->apply($conflictImport, Transitions::RESET);
        }
        $this->em->flush();
    }

    /**
     * @param Queue $item
     * @param Import      $import
     */
    private function finishCreationQueue(Queue $item, Import $import): void
    {
        if (QueueState::TO_CREATE !== $item->getState()) {
            throw new InvalidArgumentException("Wrong Queue creation state: ".$item->getState());
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

        /** @var BeneficiaryDuplicity $acceptedDuplicity */
        $acceptedDuplicity = $item->getAcceptedDuplicity();
        if (null !== $acceptedDuplicity) {
            $this->linkHouseholdToQueue($import, $createdHousehold, $acceptedDuplicity->getDecideBy());
        } else {
            $this->linkHouseholdToQueue($import, $createdHousehold, $import->getCreatedBy());
        }
        $this->logImportInfo($import, "Created Household #{$createdHousehold->getId()}");

        WorkflowTool::checkAndApply($this->importQueueStateMachine, $item, [QueueTransitions::CREATE]);
    }

    /**
     * @param Queue $item
     * @param Import      $import
     *
     * @throws EntityNotFoundException
     */
    private function finishUpdateQueue(Queue $item, Import $import): void
    {
        if (QueueState::TO_UPDATE !== $item->getState()) {
            throw new InvalidArgumentException("Wrong Queue state");
        }

        /** @var BeneficiaryDuplicity $acceptedDuplicity */
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

        WorkflowTool::checkAndApply($this->importQueueStateMachine, $item, [QueueTransitions::UPDATE]);
    }

    private function linkHouseholdToQueue(Import $import, Household $household, User $decide): void
    {
        foreach ($household->getBeneficiaries() as $beneficiary) {
            $beneficiaryInImport = new ImportBeneficiary($import, $beneficiary, $decide);
            $this->em->persist($beneficiaryInImport);
        }
    }
}
