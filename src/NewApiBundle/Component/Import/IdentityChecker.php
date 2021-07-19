<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\NationalId;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use Psr\Log\LoggerInterface;

class IdentityChecker
{
    use ImportLoggerTrait;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function check(Import $import)
    {
        if (ImportState::IDENTITY_CHECKING !== $import->getState()) {
            throw new \BadMethodCallException('Unable to execute checker. Import is not ready to check.');
        }

        $this->preCheck($import);

        foreach ($this->getItemsToCheck($import) as $i => $item) {
            $this->checkOne($item);

            if ($i % 500 === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();

        $this->postCheck($import);
    }

    protected function checkOne(ImportQueue $item)
    {
        $found = false;

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
        $index = 0;
        foreach ($item->getContent() as $c) {
            if (empty($c['ID Type']) || empty($c['ID Number'])) {
                $this->logImportDebug($item->getImport(), "[Queue#{$item->getId()}|line#$index] Duplicity checking omitted because of missing ID information");
                continue;
            }

            $bnfDuplicities = $this->entityManager->getRepository(Beneficiary::class)->findIdentityByNationalId(
                $item->getImport()->getProject()->getIso3(),
                (string) $c['ID Type'],
                (string) $c['ID Number']
            );

            if (count($bnfDuplicities) > 0) {
                $this->logImportInfo($item->getImport(), "Found ".count($bnfDuplicities)." duplicities for {$c['ID Type']} {$c['ID Number']}");
                $found = true;
            } else {
                $this->logImportDebug($item->getImport(), "Found no duplicities");
            }

            foreach ($bnfDuplicities as $bnf) {

                if (!array_key_exists($bnf->getHousehold()->getId(), $duplicities)) {
                    $duplicities[$bnf->getHousehold()->getId()] = new ImportBeneficiaryDuplicity($item, $bnf->getHousehold());
                    $duplicities[$bnf->getHousehold()->getId()]->setDecideAt(new \DateTime('now'));
                }
                $importDuplicity = $duplicities[$bnf->getHousehold()->getId()];
                $importDuplicity->addReason("Queue#{$item->getId()} <=> Beneficiary#{$bnf->getId()}");

                $this->entityManager->persist($item);
                $this->entityManager->persist($importDuplicity);
                $this->logImportInfo($item->getImport(),
                    "Found duplicity with existing records: Queue#{$item->getId()} <=> Beneficiary#{$bnf->getId()}");
            }
            $index++;
        }

        $item->setState($found ? ImportQueueState::SUSPICIOUS : ImportQueueState::VALID);
        $this->entityManager->persist($item);
    }

    private function preCheck(Import $import)
    {
        //does not work - doctrine won't arrange queries in proper order
        /*$this->entityManager->createQueryBuilder()
            ->update(ImportQueue::class, 'iq')
            ->set('iq.state', '?1')
            ->andWhere('iq.import = ?2')
            ->setParameter('1', ImportQueueState::NEW)
            ->setParameter('2', $import->getId())
            ->getQuery()
            ->execute();*/

        $importQueues = $this->entityManager->getRepository(ImportQueue::class)
            ->findBy([
                'import' => $import,
                'state' => ImportQueueState::VALID,
            ]);

        /** @var ImportQueue $importQueue */
        foreach ($importQueues as $importQueue) {
            $importQueue->setState(ImportQueueState::NEW);
        }

        $this->entityManager->flush();
    }

    private function postCheck(Import $import)
    {
        $isInvalid = $this->isImportQueueInvalid($import);
        $import->setState($isInvalid ? ImportState::IDENTITY_CHECK_FAILED : ImportState::IDENTITY_CHECK_CORRECT);

        $this->entityManager->persist($import);
        $this->entityManager->flush();
        $this->logImportDebug($import, "Ended with status ".$import->getState());
    }

    /**
     * @param Import $import
     *
     * @return ImportQueue[]
     */
    private function getItemsToCheck(Import $import): iterable
    {
        return $this->entityManager->getRepository(ImportQueue::class)
            ->findBy(['import' => $import, 'state' => ImportQueueState::NEW]);
    }

    /**
     * @param Import $import
     *
     * @return bool
     */
    public function isImportQueueInvalid(Import $import): bool
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
