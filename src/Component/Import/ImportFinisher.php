<?php

declare(strict_types=1);

namespace Component\Import;

use Entity\Household;
use Exception;
use Utils\HouseholdService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use Component\Import\Finishing;
use Component\Import\Finishing\HouseholdDecoratorBuilder;
use Entity\Import;
use Entity\ImportBeneficiary;
use Entity\ImportHouseholdDuplicity;
use Entity\ImportQueue;
use Enum\ImportQueueState;
use Repository\ImportQueueRepository;
use Workflow\ImportQueueTransitions;
use Entity\Project;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Entity\User;

class ImportFinisher
{
    use ImportLoggerTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly HouseholdService $householdService,
        LoggerInterface $logger,
        private readonly WorkflowInterface $importStateMachine,
        private readonly WorkflowInterface $importQueueStateMachine,
        private readonly Finishing\HouseholdDecoratorBuilder $householdDecoratorBuilder,
        private readonly ImportQueueRepository $queueRepository,
        private readonly ManagerRegistry $managerRegistry
    ) {
        $this->logger = $logger;
    }

    public function finishCreationQueue(ImportQueue $item, Import $import): void
    {
        if (ImportQueueState::TO_CREATE !== $item->getState()) {
            throw new InvalidArgumentException("Wrong ImportQueue creation state: " . $item->getState());
        }

        $createdHousehold = $this->householdService->create(
            $this->householdDecoratorBuilder->buildHouseholdInputType($item),
            $import->getCountryIso3()
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
        $this->em->flush();
    }

    /**
     *
     * @throws EntityNotFoundException|Exception
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
        $projects = array_values(
            array_map(fn(Project $project) => $project->getId(), $updatedHousehold->getProjects()->toArray())
        );

        foreach ($import->getProjects() as $project) {
            $projects[] = $project->getId();
        }

        $householdUpdateInputType->setProjectIds($projects);

        $updatedHousehold = $acceptedDuplicity->getTheirs();
        $this->householdService->update($updatedHousehold, $householdUpdateInputType, $import->getCountryIso3());

        $this->linkHouseholdToQueue($import, $updatedHousehold, $acceptedDuplicity->getDecideBy());
        $this->logImportInfo($import, "Updated Household #{$updatedHousehold->getId()}");

        $this->importQueueStateMachine->apply($item, ImportQueueTransitions::UPDATE);
        $this->em->flush();
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
        $this->em->flush();
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
