<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use BeneficiaryBundle\Entity\Beneficiary;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
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

    /** @var WorkflowInterface */
    private $importStateMachine;

    /** @var WorkflowInterface */
    private $importQueueStateMachine;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface        $logger,
        WorkflowInterface      $importStateMachine,
        WorkflowInterface      $importQueueStateMachine
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->queueRepository = $this->entityManager->getRepository(ImportQueue::class);
        $this->importStateMachine = $importStateMachine;
        $this->importQueueStateMachine = $importQueueStateMachine;
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
        /* probably works but we have bad testing data
        $ids = $this->findInQueue($item);
        foreach ($ids as $id) {
            $importDuplicity = new ImportQueueDuplicity($item, $id);
            $importDuplicity->setDecideAt(new \DateTime('now'));
            $this->entityManager->persist($importDuplicity);

            $item->setState(ImportQueueState::SUSPICIOUS);
            $this->entityManager->persist($item);
            $found = true;
        }
        */

        $index = 0;
        $bnfDuplicities = [];
        $duplicities = [];
        foreach ($item->getContent() as $c) {
            if (empty($c['ID Type'][CellParameters::VALUE]) || empty($c['ID Number'][CellParameters::VALUE])) {
                $this->logImportDebug($item->getImport(),
                    "[Queue#{$item->getId()}|line#$index] Duplicity checking omitted because of missing ID information");
                continue;
            }

            $bnfDuplicities = $this->entityManager->getRepository(Beneficiary::class)->findIdentity(
                (string) $c['ID Type'][CellParameters::VALUE],
                (string) $c['ID Number'][CellParameters::VALUE],
                $item->getImport()->getProject()->getIso3()
            );

            if (count($bnfDuplicities) > 0) {
                $this->logImportInfo($item->getImport(), "Found ".count($bnfDuplicities)." duplicities for {$c['ID Type'][CellParameters::VALUE]} {$c['ID Number'][CellParameters::VALUE]}");
            } else {
                $this->logImportDebug($item->getImport(), "Found no duplicities");
            }

            foreach ($bnfDuplicities as $bnf) {
                if (!array_key_exists($bnf->getHousehold()->getId(), $duplicities)) {
                    $duplicity = new ImportBeneficiaryDuplicity($item, $bnf->getHousehold());
                    $duplicity->setDecideAt(new \DateTime('now'));
                    $item->getImportBeneficiaryDuplicities()->add($duplicity);
                    $this->entityManager->persist($duplicity);

                    $duplicities[$bnf->getHousehold()->getId()] = $duplicity;
                }
                $importDuplicity = $duplicities[$bnf->getHousehold()->getId()];
                $importDuplicity->addReason("Queue#{$item->getId()} <=> Beneficiary#{$bnf->getId()}");

                $this->logImportInfo($item->getImport(),
                    "Found duplicity with existing records: Queue#{$item->getId()} <=> Beneficiary#{$bnf->getId()}");
            }
            $index++;
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
