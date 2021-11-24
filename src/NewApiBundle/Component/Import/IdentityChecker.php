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

            if ($this->importQueueStateMachine->can($item, ImportQueueTransitions::SUSPICIOUS)) {
                $this->importQueueStateMachine->apply($item, ImportQueueTransitions::SUSPICIOUS);
            }

            if ($i % 500 === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();

        $queueSize = $this->queueRepository->countItemsToIdentityCheck($import);
        if (0 === $queueSize) {
            $this->logImportInfo($import, 'Batch ended - nothing left, identity checking ends');
            $this->logImportDebug($import, "Ended with status ".$import->getState());
            WorkflowTool::checkAndApply($this->importStateMachine, $import, [ImportTransitions::COMPLETE_IDENTITY, ImportTransitions::FAIL_IDENTITY]);
        } else {
            $this->logImportInfo($import, "Batch ended - $queueSize items left, identity checking continues");
        }
    }

    /**
     * @param ImportQueue $item
     *
     * @return Beneficiary[]
     */
    public function getItemDuplicities(ImportQueue $item): array
    {
        $index = 0;
        $bnfDuplicities = [];
        foreach ($item->getContent() as $c) {
            if (empty($c['ID Type']) || empty($c['ID Number'])) {
                $this->logImportDebug($item->getImport(),
                    "[Queue#{$item->getId()}|line#$index] Duplicity checking omitted because of missing ID information");
                continue;
            }

            $bnfDuplicities = $this->entityManager->getRepository(Beneficiary::class)->findIdentity(
                (string) $c['ID Type'],
                (string) $c['ID Number'],
                $item->getImport()->getProject()->getIso3()
            );

            if (count($bnfDuplicities) > 0) {
                $this->logImportInfo($item->getImport(), "Found ".count($bnfDuplicities)." duplicities for {$c['ID Type']} {$c['ID Number']}");
            } else {
                $this->logImportDebug($item->getImport(), "Found no duplicities");
            }

            $index++;
        }

        return $bnfDuplicities;
    }

    public function validateItem(ImportQueue $item): void
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
        $duplicities = [];
        $bnfDuplicities = $this->getItemDuplicities($item);

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

        $item->setIdentityCheckedAt(new \DateTime());
        $this->entityManager->persist($item);
    }

    /**
     * @param Import $import
     *
     * @return bool
     */
    public function isImportQueueSuspicious(Import $import): bool
    {
        $queue = $this->entityManager->getRepository(ImportQueue::class)
            ->findBy(['import' => $import, 'state' => ImportQueueState::SUSPICIOUS]);

        return count($queue) > 0;
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
