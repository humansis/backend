<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Utils\HouseholdExportCSVService;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\Integrity;
use NewApiBundle\Component\Import\Finishing;
use NewApiBundle\Component\Import\Finishing\BeneficiaryDecoratorBuilder;
use NewApiBundle\Component\Import\Integrity\DuplicityService;
use NewApiBundle\Component\Import\Integrity\ImportLineFactory;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportFile;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\InputType\Beneficiary\BeneficiaryInputType;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Workflow\ImportQueueTransitions;
use NewApiBundle\Workflow\ImportTransitions;
use Symfony\Component\Validator\ConstraintViolationInterface;
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

    /** @var DuplicityService */
    private $duplicityService;

    public function __construct(
        ValidatorInterface                    $validator,
        EntityManagerInterface                $entityManager,
        WorkflowInterface                     $importStateMachine,
        WorkflowInterface                     $importQueueStateMachine,
        Integrity\ImportLineFactory           $importLineFactory,
        Integrity\DuplicityService            $duplicityService,
        Finishing\HouseholdDecoratorBuilder   $householdDecoratorBuilder,
        Finishing\BeneficiaryDecoratorBuilder $beneficiaryDecoratorBuilder
    ) {
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->queueRepository = $this->entityManager->getRepository(ImportQueue::class);
        $this->importStateMachine = $importStateMachine;
        $this->importQueueStateMachine = $importQueueStateMachine;
        $this->importLineFactory = $importLineFactory;
        $this->duplicityService = $duplicityService;
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
                if (!$item->hasColumnViolation($index, HouseholdExportCSVService::ID_NUMBER) && !$item->hasColumnViolation($index,
                        HouseholdExportCSVService::ID_TYPE)) {
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
            $household = $this->householdDecoratorBuilder->buildHouseholdInputType($item);
            $violations = $this->validator->validate($household, null, ["Default", "HouseholdCreateInputType", "Strict"]);
            foreach ($violations as $violation) {
                $item->addViolation($this->buildNormalizedErrorMessage($violation, 0));
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

    private function buildErrorMessage(ConstraintViolationInterface $violation, int $lineIndex): Integrity\QueueViolation
    {
        $property = $violation->getConstraint()->payload['propertyPath'] ?? $violation->getPropertyPath();

        static $mapping;
        if (null === $mapping) {
            $mapping = array_flip(HouseholdExportCSVService::MAPPING_PROPERTIES);
            foreach ($this->entityManager->getRepository(CountrySpecific::class)->findAll() as $countrySpecific) {
                $mapping['countrySpecifics.'.$countrySpecific->getId()] = $countrySpecific->getFieldString();
            }
        }

        return Integrity\QueueViolation::create($lineIndex, $mapping[$property], $violation->getMessage(), $violation->getInvalidValue());
    }

    private function buildNormalizedErrorMessage(ConstraintViolationInterface $violation, int $lineIndex): Integrity\QueueViolation
    {
        $property = $violation->getConstraint()->payload['propertyPath'] ?? $violation->getPropertyPath();

        return Integrity\QueueViolation::create($lineIndex, $property, $violation->getMessage(), $violation->getInvalidValue());
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

    /**
     * @param ImportQueue          $importQueue
     * @param int                  $index
     * @param BeneficiaryInputType $beneficiaryInputType
     *
     * @return void
     */
    private function checkFileDuplicity(ImportQueue $importQueue, int $index, BeneficiaryInputType $beneficiaryInputType): void
    {
        $cards = $beneficiaryInputType->getNationalIdCards();
        if (count($cards) > 0) {
            $idCard = $cards[0];
            $nationalIdCount = $this->duplicityService->getIdentityCount($importQueue->getImport(), $idCard);
            if ($nationalIdCount > 1) {
                $importQueue->addViolation(Integrity\QueueViolation::create($index, HouseholdExportCSVService::ID_TYPE,
                    'This line has ID duplicity!',
                    sprintf('%s: %s', $idCard->getType(), $idCard->getNumber())));
                $importQueue->addViolation(Integrity\QueueViolation::create($index, HouseholdExportCSVService::ID_NUMBER,
                    'This line has ID duplicity!',
                    sprintf('%s: %s', $idCard->getType(), $idCard->getNumber())));
            }
        }
    }
}
