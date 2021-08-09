<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use BeneficiaryBundle\Utils\HouseholdExportCSVService;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\Integrity;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Repository\ImportQueueRepository;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class IntegrityChecker
{
    const BATCH_SIZE = 1;

    /** @var ValidatorInterface */
    private $validator;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var ImportQueueRepository */
    private $queueRepository;

    public function __construct(ValidatorInterface $validator, EntityManagerInterface $entityManager)
    {
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->queueRepository = $this->entityManager->getRepository(ImportQueue::class);
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

        foreach ($this->queueRepository->getItemsToIntegrityCheck($import, $batchSize) as $i => $item) {
            $this->checkOne($item);

            if ($i % 500 === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();

        if (0 === $this->queueRepository->countItemsToIntegrityCheck($import)) {
            $isInvalid = $this->isImportQueueInvalid($import);
            $import->setState($isInvalid ? ImportState::INTEGRITY_CHECK_FAILED : ImportState::INTEGRITY_CHECK_CORRECT);

            $this->entityManager->persist($import);
            $this->entityManager->flush();
        }
    }

    protected function checkOne(ImportQueue $item)
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

        if ($anyViolation) {
            $message['raw'] = $item->getContent();

            $item->setMessage(json_encode($message));
            $item->setState(ImportQueueState::INVALID);
        } else {
            $item->setState(ImportQueueState::VALID);
        }

        // $item->setIntegrityCheckedAt(new \DateTime());

        $this->entityManager->persist($item);
    }

    /**
     * @param Import $import
     *
     * @return bool
     */
    private function isImportQueueInvalid(Import $import): bool
    {
        $invalidQueue = $this->entityManager->getRepository(ImportQueue::class)
            ->findBy(['import' => $import, 'state' => ImportQueueState::INVALID]);

        $validQueue = $this->entityManager->getRepository(ImportQueue::class)
            ->findBy(['import' => $import, 'state' => ImportQueueState::VALID]);

        return count($invalidQueue) > 0 || count($validQueue) === 0;
    }

    private function buildErrorMessage(ConstraintViolationInterface $violation)
    {
        $property = $violation->getConstraint()->payload['propertyPath'] ?? $violation->getPropertyPath();

        static $mapping;
        if (null === $mapping) {
            $mapping = array_flip(HouseholdExportCSVService::MAPPING_PROPERTIES);
        }

        return ['column' => $mapping[$property], 'violation' => $violation->getMessage(), 'value' => $violation->getInvalidValue()];
    }
}
