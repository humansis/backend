<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use BeneficiaryBundle\Entity\Beneficiary;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\Integrity\ImportLine;
use NewApiBundle\Component\Import\Integrity\ImportLineFactory;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Entity\ImportHouseholdDuplicity;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Workflow\ImportQueueTransitions;
use NewApiBundle\Workflow\ImportTransitions;
use NewApiBundle\Workflow\WorkflowTool;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class IdentityChecker
{
    use ImportLoggerTrait;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ImportQueueRepository */
    private $queueRepository;

    /** @var ImportLineFactory */
    private $importLineFactory;

    /** @var WorkflowInterface */
    private $importStateMachine;

    /** @var WorkflowInterface */
    private $importQueueStateMachine;

    public function __construct(
        EntityManagerInterface      $entityManager,
        LoggerInterface             $logger,
        WorkflowInterface           $importStateMachine,
        WorkflowInterface           $importQueueStateMachine,
        Integrity\ImportLineFactory $importLineFactory
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->queueRepository = $this->entityManager->getRepository(ImportQueue::class);
        $this->importStateMachine = $importStateMachine;
        $this->importQueueStateMachine = $importQueueStateMachine;
        $this->importLineFactory = $importLineFactory;
    }

    /**
     * @param Import   $import
     * @param int|null $batchSize if null => all
     */
    public function check(Import $import, ?int $batchSize = null)
    {
        if (ImportState::IDENTITY_CHECKING !== $import->getState()) {
            throw new \BadMethodCallException('Unable to execute checker. Import is not ready to check.');
        }

        foreach ($this->queueRepository->getItemsToIdentityCheck($import, $batchSize) as $i => $item) {
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
        $duplicities = $this->validateItemDuplicities($item);
        if (count($duplicities) > 0) {
            $this->logImportWarning($item->getImport(), "Found duplicity!");
            $this->importQueueStateMachine->apply($item, ImportQueueTransitions::IDENTITY_CANDIDATE);
        } else {
            $this->logImportDebug($item->getImport(), "Duplicity check OK");
            $this->importQueueStateMachine->apply($item, ImportQueueTransitions::UNIQUE_CANDIDATE);
        }
    }

    /**
     * @param ImportQueue $item
     *
     * @return Beneficiary[]
     */
    protected function validateItemDuplicities(ImportQueue $item): array
    {
        $index = -1;
        $bnfDuplicities = [];
        foreach ($this->importLineFactory->createAll($item) as $line) {
            $index++;
            $IDType = $line->idType;
            $IDNumber = $line->idNumber;
            if (empty($IDType) || empty($IDNumber)) {
                $this->logImportDebug($item->getImport(),
                    "[Queue#{$item->getId()}|line#$index] Duplicity checking omitted because of missing ID information");
                continue;
            }

            $bnfDuplicities = $this->entityManager->getRepository(Beneficiary::class)->findIdentity(
                (string) $IDType,
                (string) $IDNumber,
                $item->getImport()->getProject()->getIso3()
            );

            if (count($bnfDuplicities) > 0) {
                $this->logImportInfo($item->getImport(), "Found ".count($bnfDuplicities)." duplicities for $IDType $IDNumber");
            } else {
                $this->logImportDebug($item->getImport(), "Found no duplicities");
            }

            foreach ($bnfDuplicities as $bnf) {
                $item->addDuplicity($index, $bnf, [['ID Type'=>$IDType, 'ID Number'=>$IDNumber]]);

                $this->logImportInfo($item->getImport(),
                    "Found duplicity with existing records: Queue#{$item->getId()} <=> Beneficiary#{$bnf->getId()}");
            }
        }

        $item->setIdentityCheckedAt(new \DateTime());
        $this->entityManager->persist($item);

        return $bnfDuplicities;
    }

    /**
     * @param Import $import
     *
     * @return bool
     */
    public function isImportQueueUnresolvedSuspicious(Import $import): bool
    {
        $queue = $this->entityManager->getRepository(ImportQueue::class)
            ->findBy(['import' => $import, 'state' => ImportQueueState::IDENTITY_CANDIDATE]);

        /** @var ImportQueue $item */
        foreach ($queue as $item) {
            if (!$item->hasResolvedDuplicities()) return true;
        }
        return false;
    }

    public function getSuspiciousItems(Import $import): iterable
    {
        return $this->entityManager->getRepository(ImportQueue::class)
            ->findBy(['import' => $import, 'state' => ImportQueueState::IDENTITY_CANDIDATE], ['id'=>'asc']);
    }

    public function isImportQueueSuspicious(Import $import): bool
    {
        $queue = $this->entityManager->getRepository(ImportQueue::class)
            ->count(['import' => $import, 'state' => ImportQueueState::IDENTITY_CANDIDATE]);

        return $queue > 0;
    }

    /**
     * @param ImportQueue $current
     *
     * @return ImportQueue[]
     */
    private function findInQueue(ImportQueue $current)
    {
        $founded = [];

        foreach ($current->getContent() as $c) {
            /** @var ImportQueue[] $items */
            $items = $this->entityManager->getRepository(ImportQueue::class)->findInContent($current->getImport(), (string) $c['ID Number']);
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
