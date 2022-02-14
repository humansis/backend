<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use BeneficiaryBundle\Utils\HouseholdExportCSVService;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\Integrity;
use NewApiBundle\Component\Import\Finishing;
use NewApiBundle\Component\Import\Finishing\BeneficiaryDecoratorBuilder;
use NewApiBundle\Component\Import\Integrity\ImportLineFactory;
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

    /** @var ImportLineFactory */
    private $importLineFactory;

    /** @var Finishing\HouseholdDecoratorBuilder */
    private $householdDecoratorBuilder;

    /** @var BeneficiaryDecoratorBuilder */
    private $beneficiaryDecoratorBuilder;

    /** @var WorkflowInterface */
    private $importStateMachine;

    /** @var WorkflowInterface */
    private $importQueueStateMachine;

    public function __construct(
        ValidatorInterface                    $validator,
        EntityManagerInterface                $entityManager,
        WorkflowInterface                     $importStateMachine,
        WorkflowInterface                     $importQueueStateMachine,
        Integrity\ImportLineFactory           $importLineFactory,
        Finishing\HouseholdDecoratorBuilder   $householdDecoratorBuilder,
        Finishing\BeneficiaryDecoratorBuilder $beneficiaryDecoratorBuilder
    ) {
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->queueRepository = $this->entityManager->getRepository(ImportQueue::class);
        $this->importStateMachine = $importStateMachine;
        $this->importQueueStateMachine = $importQueueStateMachine;
        $this->importLineFactory = $importLineFactory;
        $this->householdDecoratorBuilder = $householdDecoratorBuilder;
        $this->beneficiaryDecoratorBuilder = $beneficiaryDecoratorBuilder;
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
            $this->importStateMachine->apply($import, ImportTransitions::FAIL_INTEGRITY);
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
        if (in_array($item->getState(), [ImportQueueState::INVALID, ImportQueueState::VALID])) {
            return; // there is nothing to check
        }
        if ($item->getState() !== ImportQueueState::NEW) {
            throw new \InvalidArgumentException("Wrong ImportQueue state for Integrity check: ".$item->getState());
        }
        $this->validateItem($item);
        if ($item->hasViolations()) {
            $this->importQueueStateMachine->apply($item, ImportQueueTransitions::INVALIDATE);
        } else {
            $this->importQueueStateMachine->apply($item, ImportQueueTransitions::VALIDATE);
        }

        $this->entityManager->persist($item);
        $this->entityManager->flush();
    }

    /**
     * @param ImportQueue $item
     */
    private function validateItem(ImportQueue $item): void
    {
        $iso3 = $item->getImport()->getCountryIso3();

        $householdLine = $this->importLineFactory->create($item, 0);
        $violations = $this->validator->validate($householdLine, null, ["household"]);

        foreach ($violations as $violation) {
            $item->addViolation(0, $this->buildErrorMessage($violation));
        }

        $index = 1;
        foreach ($item->getMemberContents() as $beneficiaryContent) {
            $hhm = $this->importLineFactory->create($item, $index);
            $violations = $this->validator->validate($hhm, null, ["member"]);

            foreach ($violations as $violation) {
                $item->addViolation($index, $this->buildErrorMessage($violation));
            }
            $index++;
        }

        $index = 0;
        foreach ($this->importLineFactory->createAll($item) as $hhm) {
            if ($item->hasViolations($index)) continue; // don't do complex checking if there are simple errors

            $beneficiary = $this->beneficiaryDecoratorBuilder->buildBeneficiaryInputType($hhm);
            $violations = $this->validator->validate($beneficiary, null, ["BeneficiaryInputType", "Strict"]);

            foreach ($violations as $violation) {
                $item->addViolation($index, $this->buildNormalizedErrorMessage($violation));
            }
            $index++;
        }

        if (!$item->hasViolations()) { // don't do complex checking if there are simple errors
            $household = $this->householdDecoratorBuilder->buildHouseholdInputType($item);
            $violations = $this->validator->validate($household, null, ["HouseholdCreateInputType", "Strict"]);
            foreach ($violations as $violation) {
                $item->addViolation(0, $this->buildNormalizedErrorMessage($violation));
            }
        }
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

        return ['column' => ucfirst($mapping[$property]), 'violation' => $violation->getMessage(), 'value' => $violation->getInvalidValue()];
    }

    public function buildNormalizedErrorMessage(ConstraintViolationInterface $violation)
    {
        $property = $violation->getConstraint()->payload['propertyPath'] ?? $violation->getPropertyPath();

        return ['column' => ucfirst($property), 'violation' => $violation->getMessage(), 'value' => $violation->getInvalidValue()];
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
