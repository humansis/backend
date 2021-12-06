<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use BeneficiaryBundle\Utils\HouseholdExportCSVService;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\Integrity;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportFile;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Workflow\ImportQueueTransitions;
use NewApiBundle\Workflow\ImportTransitions;
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

    /** @var ImportQueueRepository */
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
        $this->queueRepository = $this->entityManager->getRepository(ImportQueue::class);
        $this->importStateMachine = $importStateMachine;
        $this->importQueueStateMachine = $importQueueStateMachine;
    }

    /**
     * @param Import   $import
     * @param int|null $batchSize if null => all
     */
    public function check(Import $import, ?int $batchSize = null): void
    {
        if (ImportState::INTEGRITY_CHECKING !== $import->getState()) {
            throw new \BadMethodCallException('Unable to execute checker. Import is not ready to integrity check.');
        }

        if ($this->hasImportValidFile($import) === false) {
            WorkflowTool::checkAndApply($this->importStateMachine, $import, [ImportTransitions::FAIL_INTEGRITY]);

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
     * @param ImportQueue $item
     */
    protected function checkOne(ImportQueue $item): void
    {
        $violations = $this->getQueueItemViolations($item);
        $message = $violations['message'];
        if ($violations['hasViolations']) {
            $message['raw'] = $item->getContent();
            $item->setMessage(json_encode($message));
            $this->importQueueStateMachine->apply($item, ImportQueueTransitions::INVALIDATE);
        } else {
            $this->importQueueStateMachine->apply($item, ImportQueueTransitions::VALIDATE);
        }

        $this->entityManager->persist($item);
        $this->entityManager->flush();
    }

    /**
     * @param ImportQueue $item
     *
     * @return array
     */
    private function getQueueItemViolations(ImportQueue $item): array
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

    public function hasImportQueueInvalidItems(Import $import): bool
    {
        $invalidQueue = $this->entityManager->getRepository(ImportQueue::class)
            ->findBy(['import' => $import, 'state' => ImportQueueState::INVALID]);

        return count($invalidQueue) > 0;
    }

    public function isImportWithoutContent(Import $import): bool
    {
        $queueSize = $this->entityManager->getRepository(ImportQueue::class)
            ->count([
                'import' => $import,
                'state' => [ImportQueueState::NEW, ImportQueueState::INVALID, ImportQueueState::VALID]
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
        return (0 != $this->entityManager->getRepository(ImportFile::class)->count([
                'import' => $import,
                'structureViolations' => null,
            ]));
    }
}
