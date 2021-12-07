<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Integrity;

use BeneficiaryBundle\Utils\HouseholdExportCSVService;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\Integrity;
use NewApiBundle\Component\Import\Entity\Import;
use NewApiBundle\Component\Import\Entity\File;
use NewApiBundle\Component\Import\Entity\Queue;
use NewApiBundle\Component\Import\Enum\QueueState;
use NewApiBundle\Component\Import\Enum\State;
use NewApiBundle\Component\Import\Repository\QueueRepository;
use NewApiBundle\Component\Import\Enum\QueueTransitions;
use NewApiBundle\Component\Import\Enum\Transitions;
use NewApiBundle\Workflow\WorkflowTool;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class IntegrityChecker
{
    /** @var ValidatorInterface */
    private $validator;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var QueueRepository */
    private $queueRepository;

    /** @var WorkflowInterface */
    private $importStateMachine;

    /** @var WorkflowInterface */
    private $importQueueStateMachine;

    public function __construct(
        ValidatorInterface     $validator,
        EntityManagerInterface $entityManager,
        WorkflowInterface      $importStateMachine,
        WorkflowInterface      $importQueueStateMachine
    ) {
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->queueRepository = $this->entityManager->getRepository(Queue::class);
        $this->importStateMachine = $importStateMachine;
        $this->importQueueStateMachine = $importQueueStateMachine;
    }

    /**
     * @param Import   $import
     * @param int|null $batchSize if null => all
     */
    public function check(Import $import, ?int $batchSize = null): void
    {
        if (State::INTEGRITY_CHECKING !== $import->getState()) {
            throw new \BadMethodCallException('Unable to execute checker. Import is not ready to integrity check.');
        }

        if ($this->hasImportValidFile($import) === false) {
            WorkflowTool::checkAndApply($this->importStateMachine, $import, [Transitions::FAIL_INTEGRITY]);

            return;
        }

        foreach ($this->queueRepository->getItemsToIntegrityCheck($import, $batchSize) as $i => $item) {
            $this->checkOne($item);
            if ($i % 500 === 0) {
                $this->entityManager->flush();
            }
        }
        $this->entityManager->flush();
    }

    /**
     * @param Queue $item
     */
    protected function checkOne(Queue $item): void
    {
        $violations = $this->getQueueItemViolations($item);
        $message = $violations['message'];
        if ($violations['hasViolations']) {
            $message['raw'] = $item->getContent();
            $item->setMessage(json_encode($message));
            $this->importQueueStateMachine->apply($item, QueueTransitions::INVALIDATE);
        } else {
            $this->importQueueStateMachine->apply($item, QueueTransitions::VALIDATE);
        }

        $this->entityManager->persist($item);
        $this->entityManager->flush();
    }

    /**
     * @param Queue $item
     *
     * @return array
     */
    private function getQueueItemViolations(Queue $item): array
    {
        $iso3 = $item->getImport()->getProject()->getIso3();

        $message = [];
        $violationList = new ConstraintViolationList();
        $violationList->addAll(
            $this->validator->validate(new Integrity\HouseholdHead($item->getHeadContent(), $iso3, $this->entityManager))
        );
        $anyViolation = false;
        $message[0] = [];
        foreach ($violationList as $violation) {
            $message[0][] = $this->buildErrorMessage($violation);
            $anyViolation = true;
        }

        $index = 1;
        foreach ($item->getMemberContents() as $memberContent) {
            $message[$index] = [];
            $violationList = new ConstraintViolationList();
            $violationList->addAll(
                $this->validator->validate(new Integrity\HouseholdMember($memberContent, $iso3, $this->entityManager))
            );

            foreach ($violationList as $violation) {
                $message[$index][] = $this->buildErrorMessage($violation);
                $anyViolation = true;
            }
            $index++;
        }

        return ['hasViolations' => $anyViolation, 'message' => $message];
    }

    public function hasQueueInvalidItems(Import $import): bool
    {
        $invalidQueue = $this->entityManager->getRepository(Queue::class)
            ->findBy(['import' => $import, 'state' => QueueState::INVALID]);

        return count($invalidQueue) > 0;
    }

    public function isImportWithoutContent(Import $import): bool
    {
        $queueSize = $this->entityManager->getRepository(Queue::class)
            ->count([
                'import' => $import,
                'state' => [QueueState::NEW, QueueState::INVALID, QueueState::VALID]
            ]);

        return $queueSize == 0;
    }

    public function buildErrorMessage(ConstraintViolationInterface $violation)
    {
        $property = $violation->getConstraint()->payload['propertyPath'] ?? $violation->getPropertyPath();

        static $mapping;
        if (null === $mapping) {
            $mapping = array_flip(HouseholdExportCSVService::MAPPING_PROPERTIES);
        }

        return ['column' => $mapping[$property], 'violation' => $violation->getMessage(), 'value' => $violation->getInvalidValue()];
    }

    /**
     * @param Import $import
     *
     * @return bool
     */
    private function hasImportValidFile(Import $import): bool
    {
        return (0 != $this->entityManager->getRepository(File::class)->count([
                'import' => $import,
                'structureViolations' => null,
            ]));
    }
}
