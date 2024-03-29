<?php

declare(strict_types=1);

namespace Component\Import;

use BadMethodCallException;
use DateTime;
use Entity\Beneficiary;
use Entity\NationalId;
use Doctrine\ORM\EntityManagerInterface;
use Component\Import\Identity\NationalIdHashSet;
use Component\Import\Integrity;
use Component\Import\Integrity\ImportLineFactory;
use Entity\Import;
use Entity\ImportQueue;
use Enum\EnumValueNoFoundException;
use Enum\ImportQueueState;
use Enum\ImportState;
use Enum\NationalIdType;
use Repository\ImportQueueRepository;
use Workflow\ImportQueueTransitions;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class IdentityChecker
{
    use ImportLoggerTrait;

    private readonly \Repository\ImportQueueRepository $queueRepository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        private readonly WorkflowInterface $importStateMachine,
        private readonly WorkflowInterface $importQueueStateMachine,
        private readonly Integrity\ImportLineFactory $importLineFactory
    ) {
        $this->logger = $logger;
        $this->queueRepository = $this->entityManager->getRepository(ImportQueue::class);
    }

    /**
     * @param int|null $batchSize if null => all
     * @throws EnumValueNoFoundException
     */
    public function check(Import $import, ?int $batchSize = null)
    {
        if (ImportState::IDENTITY_CHECKING !== $import->getState()) {
            throw new BadMethodCallException('Unable to execute checker. Import is not ready to check.');
        }

        $items = $this->queueRepository->getItemsToIdentityCheck($import, $batchSize);
        $this->checkBatch($import, $items);
    }

    /**
     * @param ImportQueue[] $batch
     * @throws EnumValueNoFoundException
     */
    public function checkBatch(Import $import, iterable $items)
    {
        if (ImportState::IDENTITY_CHECKING !== $import->getState()) {
            throw new BadMethodCallException('Unable to execute checker. Import is not ready to check.');
        }

        $IDsToFind = new NationalIdHashSet();
        foreach ($items as $i => $item) {
            $this->extractItemIDs($item, $IDsToFind);
        }

        $bnfDuplicityCandidates = $this->entityManager->getRepository(Beneficiary::class)->findIdentitiesByNationalIds(
            $import->getCountryIso3(),
            $IDsToFind
        );

        /** @var Beneficiary $candidate */
        foreach ($bnfDuplicityCandidates as $candidate) {
            foreach ($candidate->getPerson()->getNationalIds() as $currentNationalId) {
                $IDsToFind->forItems(
                    $currentNationalId,
                    function (ImportQueue $item, int $index, NationalId $nationalId) use ($import, $candidate) {
                        $item->addDuplicity(
                            $index,
                            $candidate,
                            [['ID Type' => $nationalId->getIdType(), 'ID Number' => $nationalId->getIdNumber()]]
                        );
                        $this->logImportInfo(
                            $import,
                            "Found duplicity with existing records: Queue#{$item->getId()} <=> Beneficiary#{$candidate->getId()}"
                        );
                    }
                );
            }
        }
        /** @var ImportQueue $item */
        foreach ($items as $item) {
            if (count($item->getHouseholdDuplicities()) > 0) {
                $this->logImportWarning($item->getImport(), "Found duplicity!");
                $this->importQueueStateMachine->apply($item, ImportQueueTransitions::IDENTITY_CANDIDATE);
            } else {
                $this->logImportDebug($item->getImport(), "Duplicity check OK");
                $this->importQueueStateMachine->apply($item, ImportQueueTransitions::UNIQUE_CANDIDATE);

                //skip similarity check
                $this->importQueueStateMachine->apply($item, ImportQueueTransitions::TO_CREATE);
                $item->setSimilarityCheckedAt(new DateTime());
            }

            $item->setIdentityCheckedAt(new DateTime());
            $this->queueRepository->save($item);
        }
    }

    protected function checkOne(ImportQueue $item): void
    {
        $duplicities = $this->validateItemDuplicities($item);
        if (count($duplicities) > 0) {
            $this->logImportWarning($item->getImport(), "Found duplicity!");
            $this->importQueueStateMachine->apply($item, ImportQueueTransitions::IDENTITY_CANDIDATE);
        } else {
            $this->logImportDebug($item->getImport(), "Duplicity check OK");
            $this->importQueueStateMachine->apply($item, ImportQueueTransitions::UNIQUE_CANDIDATE);

            //skip similarity check
            $this->importQueueStateMachine->apply($item, ImportQueueTransitions::TO_CREATE);
        }
    }

    /**
     *
     * @throws EnumValueNoFoundException
     */
    private function extractItemIDs(ImportQueue $item, NationalIdHashSet $hashSet): void
    {
        $index = 0;
        foreach ($this->importLineFactory->createAll($item) as $line) {
            foreach ($line->getFilledIds() as $id) {
                $idType = NationalIdType::valueFromAPI($id['type']);
                $hashSet->add($item, $index, (string) $idType, (string) $id['number']);
            }
            $index++;
        }
    }

    /**
     * @return Beneficiary[]
     */
    protected function validateItemDuplicities(ImportQueue $item): array
    {
        $index = -1;
        $bnfDuplicities = [];
        foreach ($this->importLineFactory->createAll($item) as $line) {
            $index++;
            $ids = $line->getIds();
            foreach ($ids as $idItem) {
                $this->validateItemIdDuplicity($item, $index, $idItem['type'], $idItem['number']);
            }
        }

        $item->setIdentityCheckedAt(new DateTime());
        $this->entityManager->persist($item);

        return $bnfDuplicities;
    }

    private function validateItemIdDuplicity(ImportQueue $item, $index, $idType, $idNumber)
    {
        if (empty($idType) || empty($idNumber)) {
            $this->logImportDebug(
                $item->getImport(),
                "[Queue#{$item->getId()}|line#$index] Duplicity checking omitted because of missing ID information"
            );

            return;
        }

        $bnfDuplicities = $this->entityManager->getRepository(Beneficiary::class)->findIdentity(
            (string) $idType,
            (string) $idNumber,
            $item->getImport()->getCountryIso3()
        );

        if ((is_countable($bnfDuplicities) ? count($bnfDuplicities) : 0) > 0) {
            $this->logImportInfo(
                $item->getImport(),
                "Found " . (is_countable($bnfDuplicities) ? count($bnfDuplicities) : 0) . " duplicities for $idType $idNumber"
            );
        } else {
            $this->logImportDebug($item->getImport(), "Found no duplicities");
        }

        foreach ($bnfDuplicities as $bnf) {
            $item->addDuplicity($index, $bnf, [['ID Type' => $idType, 'ID Number' => $idNumber]]);

            $this->logImportInfo(
                $item->getImport(),
                "Found duplicity with existing records: Queue#{$item->getId()} <=> Beneficiary#{$bnf->getId()}"
            );
        }
    }

    public function isImportQueueUnresolvedSuspicious(Import $import): bool
    {
        $queue = $this->entityManager->getRepository(ImportQueue::class)
            ->findBy(['import' => $import, 'state' => ImportQueueState::IDENTITY_CANDIDATE]);

        /** @var ImportQueue $item */
        foreach ($queue as $item) {
            if (!$item->hasResolvedDuplicities()) {
                return true;
            }
        }

        return false;
    }

    public function getSuspiciousItems(Import $import): iterable
    {
        return $this->entityManager->getRepository(ImportQueue::class)
            ->findBy(['import' => $import, 'state' => ImportQueueState::IDENTITY_CANDIDATE], ['id' => 'asc']);
    }

    public function isImportQueueSuspicious(Import $import): bool
    {
        $queue = $this->entityManager->getRepository(ImportQueue::class)
            ->count(['import' => $import, 'state' => ImportQueueState::IDENTITY_CANDIDATE]);

        return $queue > 0;
    }

    /**
     * @return ImportQueue[]
     */
    private function findInQueue(ImportQueue $current)
    {
        $founded = [];

        foreach ($current->getContent() as $c) {
            /** @var ImportQueue[] $items */
            $items = $this->entityManager->getRepository(ImportQueue::class)->findInContent(
                $current->getImport(),
                (string) $c['ID Number']
            );
            foreach ($items as $item) {
                if ($item->getId() <= $current->getId()) {
                    continue;
                }

                foreach ($item->getContent() as $i) {
                    if ($i['ID Type'] === $c['ID Type'] && $i['ID Number'] === $c['ID Number']) {
                        $founded[] = $item;
                        break 2;
                    }
                }
            }
        }

        return $founded;
    }
}
