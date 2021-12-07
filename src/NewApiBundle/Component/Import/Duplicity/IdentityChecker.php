<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Duplicity;

use BeneficiaryBundle\Entity\Beneficiary;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\Entity\Import;
use NewApiBundle\Component\Import\Entity\BeneficiaryDuplicity;
use NewApiBundle\Component\Import\Entity\Queue;
use NewApiBundle\Component\Import\Enum\QueueState;
use NewApiBundle\Component\Import\Enum\State;
use NewApiBundle\Component\Import\Utils\ImportLoggerTrait;
use NewApiBundle\Component\Import\Repository\QueueRepository;
use NewApiBundle\Component\Import\Enum\QueueTransitions;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class IdentityChecker
{
    use ImportLoggerTrait;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var QueueRepository */
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
        $this->queueRepository = $this->entityManager->getRepository(Queue::class);
        $this->importStateMachine = $importStateMachine;
        $this->importQueueStateMachine = $importQueueStateMachine;
    }

    /**
     * @param Import   $import
     * @param int|null $batchSize if null => all
     */
    public function check(Import $import, ?int $batchSize = null)
    {
        if (State::IDENTITY_CHECKING !== $import->getState()) {
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
     * @param Queue $item
     */
    protected function checkOne(Queue $item): void
    {
        $duplicities = $this->validateItemDuplicities($item);
        if (count($duplicities) > 0) {
            $this->logImportWarning($item->getImport(), "Found duplicity!");
            $this->importQueueStateMachine->apply($item, QueueTransitions::IDENTITY_CANDIDATE);
        } else {
            $this->logImportDebug($item->getImport(), "Duplicity check OK");
            $this->importQueueStateMachine->apply($item, QueueTransitions::UNIQUE_CANDIDATE);
        }
    }

    /**
     * @param Queue $item
     *
     * @return Beneficiary[]
     */
    protected function validateItemDuplicities(Queue $item): array
    {
        $index = -1;
        $bnfDuplicities = [];
        $duplicities = [];
        foreach ($item->getContent() as $c) {
            $index++;
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
                    $duplicity = new BeneficiaryDuplicity($item, $bnf->getHousehold());
                    $duplicity->setDecideAt(new \DateTime('now'));
                    $item->getDuplicities()->add($duplicity);
                    $this->entityManager->persist($duplicity);

                    $duplicities[$bnf->getHousehold()->getId()] = $duplicity;
                }
                $importDuplicity = $duplicities[$bnf->getHousehold()->getId()];
                $importDuplicity->addReason("Queue#{$item->getId()} <=> Beneficiary#{$bnf->getId()}");

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
    public function isQueueUnresolvedSuspicious(Import $import): bool
    {
        $queue = $this->entityManager->getRepository(Queue::class)
            ->findBy(['import' => $import, 'state' => QueueState::IDENTITY_CANDIDATE]);

        /** @var Queue $item */
        foreach ($queue as $item) {
            if (!$item->hasResolvedDuplicities()) return true;
        }
        return false;
    }

    public function getSuspiciousItems(Import $import): iterable
    {
        return $this->entityManager->getRepository(Queue::class)
            ->findBy(['import' => $import, 'state' => QueueState::IDENTITY_CANDIDATE], ['id'=>'asc']);
    }

    public function isQueueSuspicious(Import $import): bool
    {
        $queue = $this->entityManager->getRepository(Queue::class)
            ->count(['import' => $import, 'state' => QueueState::IDENTITY_CANDIDATE]);

        return $queue > 0;
    }

    /**
     * @param Queue $current
     *
     * @return Queue[]
     */
    private function findInQueue(Queue $current)
    {
        $founded = [];

        foreach ($current->getContent() as $c) {
            /** @var Queue[] $items */
            $items = $this->entityManager->getRepository(Queue::class)->findInContent($current->getImport(), (string) $c['ID Number']);
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
