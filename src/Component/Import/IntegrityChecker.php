<?php

declare(strict_types=1);

namespace Component\Import;

use BadMethodCallException;
use Component\Import\Enum\ImportCsoEnum;
use Entity\CountrySpecific;
use Exception\MissingHouseholdHeadException;
use InvalidArgumentException;
use Utils\HouseholdExportCSVService;
use Doctrine\ORM\EntityManagerInterface;
use Component\Import\Finishing;
use Component\Import\Integrity;
use Component\Import\Integrity\DuplicityService;
use Component\Import\Integrity\ImportLineFactory;
use Entity\Import;
use Entity\ImportFile;
use Entity\ImportQueue;
use Enum\ImportQueueState;
use Enum\ImportState;
use InputType\Beneficiary\BeneficiaryInputType;
use Repository\ImportQueueRepository;
use Workflow\ImportQueueTransitions;
use Workflow\ImportTransitions;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class IntegrityChecker
{
    public function __construct(private readonly ValidatorInterface $validator, private readonly EntityManagerInterface $entityManager, private readonly WorkflowInterface $importStateMachine, private readonly WorkflowInterface $importQueueStateMachine, private readonly Integrity\ImportLineFactory $importLineFactory, private readonly Integrity\DuplicityService $duplicityService, private readonly Finishing\HouseholdDecoratorBuilder $householdDecoratorBuilder, private readonly Finishing\BeneficiaryDecoratorBuilder $beneficiaryDecoratorBuilder, private readonly ImportQueueRepository $queueRepository)
    {
    }

    /**
     * @param int|null $batchSize if null => all
     * @deprecated This was reworked to queues if you want to use this beaware of flush at checkOne method
     */
    public function check(Import $import, ?int $batchSize = null): void
    {
        if (ImportState::INTEGRITY_CHECKING !== $import->getState()) {
            throw new BadMethodCallException('Unable to execute checker. Import is not ready to integrity check.');
        }

        if ($this->hasImportValidFile($import) === false) {
            $this->importStateMachine->apply($import, ImportTransitions::FAIL_INTEGRITY);

            return;
        }

        foreach ($this->queueRepository->getItemsToIntegrityCheck($import, $batchSize) as $i => $item) {
            $this->checkOne($item);
            if (($i + 1) % 500 === 0) {
                $this->entityManager->flush();
            }
        }
        $this->entityManager->flush();
    }

    public function checkOne(ImportQueue $item): void
    {
        if (in_array($item->getState(), [ImportQueueState::INVALID, ImportQueueState::VALID])) {
            return; // there is nothing to check
        }
        if ($item->getState() !== ImportQueueState::NEW) {
            throw new InvalidArgumentException("Wrong ImportQueue state for Integrity check: " . $item->getState());
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

    private function validateItem(ImportQueue $item): void
    {
        $householdLine = $this->importLineFactory->create($item, 0);
        $violations = $this->validator->validate($householdLine, null, ["household"]);

        foreach ($violations as $violation) {
            $item->addViolation($this->buildErrorMessage($violation, 0));
        }

        $index = 1;
        foreach ($item->getMemberContents() as $beneficiaryContent) {
            $hhm = $this->importLineFactory->create($item, $index);
            $violations = $this->validator->validate($hhm, null, ["member"]);

            foreach ($violations as $violation) {
                $item->addViolation($this->buildErrorMessage($violation, $index));
            }
            $index++;
        }

        $index = -1;
        foreach ($this->importLineFactory->createAll($item) as $hhm) {
            $index++;
            if ($item->hasViolations($index)) {
                if (
                    !$item->hasColumnViolation(
                        $index,
                        HouseholdExportCSVService::PRIMARY_ID_NUMBER
                    ) && !$item->hasColumnViolation(
                        $index,
                        HouseholdExportCSVService::PRIMARY_ID_TYPE
                    )
                ) {
                    $beneficiary = $this->beneficiaryDecoratorBuilder->buildBeneficiaryIdentityInputType($hhm);
                    $this->checkFileDuplicity($item, $index, $beneficiary);
                }
                continue; // don't do complex checking if there are simple errors
            }

            $beneficiary = $this->beneficiaryDecoratorBuilder->buildBeneficiaryInputType($hhm);
            $violations = $this->validator->validate($beneficiary, null, ["Default", "BeneficiaryInputType", "Strict"]);
            $this->checkFileDuplicity($item, $index, $beneficiary);

            foreach ($violations as $violation) {
                $item->addViolation($this->buildNormalizedErrorMessage($violation, $index));
            }
        }

        if (!$item->hasViolations()) { // don't do complex checking if there are simple errors
            try {
                $household = $this->householdDecoratorBuilder->buildHouseholdInputType($item);
                $violations = $this->validator->validate(
                    $household,
                    null,
                    ["Default", "HouseholdCreateInputType", "Strict"]
                );
                foreach ($violations as $violation) {
                    $item->addViolation($this->buildNormalizedErrorMessage($violation, 0));
                }
            } catch (MissingHouseholdHeadException) {
                $item->addViolation(
                    Integrity\QueueViolation::create(
                        0,
                        HouseholdExportCSVService::HEAD,
                        'Household without head',
                        false
                    )
                );
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
                'state' => [ImportQueueState::NEW, ImportQueueState::INVALID, ImportQueueState::VALID],
            ]);

        return $queueSize == 0;
    }

    private function buildErrorMessage(
        ConstraintViolationInterface $violation,
        int $lineIndex
    ): Integrity\QueueViolation {
        $property = $violation->getConstraint()->payload['propertyPath'] ?? $violation->getPropertyPath();

        static $mapping;
        if (null === $mapping) {
            $mapping = array_flip(HouseholdExportCSVService::MAPPING_PROPERTIES);
            foreach ($this->entityManager->getRepository(CountrySpecific::class)->findAll() as $countrySpecific) {
                $mapping[ImportCsoEnum::MappingKey->value . '[' . $countrySpecific->getId(
                ) . ']'] = $countrySpecific->getFieldString();
                $mapping[ImportCsoEnum::MappingKey->value . '.' . $countrySpecific->getId(
                )] = $countrySpecific->getFieldString();
            }
        }
        $column = key_exists($property, $mapping) ? $mapping[$property] : $property;

        return Integrity\QueueViolation::create(
            $lineIndex,
            $column,
            $violation->getMessage(),
            $violation->getInvalidValue()
        );
    }

    private function buildNormalizedErrorMessage(
        ConstraintViolationInterface $violation,
        int $lineIndex
    ): Integrity\QueueViolation {
        $property = $violation->getConstraint()->payload['propertyPath'] ?? $violation->getPropertyPath();

        return Integrity\QueueViolation::create(
            $lineIndex,
            $property,
            $violation->getMessage(),
            $violation->getInvalidValue()
        );
    }

    private function hasImportValidFile(Import $import): bool
    {
        return (0 != $this->entityManager->getRepository(ImportFile::class)->count([
                'import' => $import,
                'structureViolations' => null,
            ]));
    }

    private function checkFileDuplicity(
        ImportQueue $importQueue,
        int $index,
        BeneficiaryInputType $beneficiaryInputType
    ): void {
        $cards = $beneficiaryInputType->getNationalIdCards();
        $columnTypes = [
            0 => HouseholdExportCSVService::PRIMARY_ID_TYPE,
            1 => HouseholdExportCSVService::SECONDARY_ID_TYPE,
            2 => HouseholdExportCSVService::TERTIARY_ID_TYPE,
        ];
        $columnNumbers = [
            0 => HouseholdExportCSVService::PRIMARY_ID_NUMBER,
            1 => HouseholdExportCSVService::SECONDARY_ID_NUMBER,
            2 => HouseholdExportCSVService::TERTIARY_ID_NUMBER,
        ];
        foreach ($cards as $cardIndex => $idCard) {
            $nationalIdCount = $this->duplicityService->getIdentityCount($importQueue->getImport(), $idCard);
            if ($nationalIdCount > 1) {
                $columnNameType = key_exists($cardIndex, $columnTypes) ? $columnTypes[$cardIndex] : HouseholdExportCSVService::PRIMARY_ID_NUMBER;
                $columnNameNumber = key_exists($cardIndex, $columnNumbers) ? $columnNumbers[$cardIndex] : HouseholdExportCSVService::PRIMARY_ID_TYPE;
                $importQueue->addViolation(
                    Integrity\QueueViolation::create(
                        $index,
                        $columnNameNumber,
                        'This line has ID duplicity!',
                        sprintf('%s: %s', $idCard->getType(), $idCard->getNumber())
                    )
                );
                $importQueue->addViolation(
                    Integrity\QueueViolation::create(
                        $index,
                        $columnNameType,
                        'This line has ID duplicity!',
                        sprintf('%s: %s', $idCard->getType(), $idCard->getNumber())
                    )
                );
            }
        }
    }
}
