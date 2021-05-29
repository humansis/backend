<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\NationalId;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Entity\ImportQueueDuplicity;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;

class IdentityChecker
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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

        $bnfs = $this->findInBeneficiaries($item);
        foreach ($bnfs as $bnf) {
            $importDuplicity = new ImportBeneficiaryDuplicity($item, $bnf);
            $importDuplicity->setDecideAt(new \DateTime('now'));
            $this->entityManager->persist($importDuplicity);

            $item->setState(ImportQueueState::SUSPICIOUS);
            $this->entityManager->persist($item);
            $found = true;
        }

        $item->setState($found ? ImportQueueState::SUSPICIOUS : ImportQueueState::VALID);
        $this->entityManager->persist($item);
    }

    private function preCheck(Import $import)
    {
        $this->entityManager->createQueryBuilder()
            ->update(ImportQueue::class, 'iq')
            ->set('iq.state', '?1')
            ->andWhere('iq.import = ?2')
            ->setParameter('1', ImportQueueState::NEW)
            ->setParameter('2', $import->getId())
            ->getQuery()
            ->execute();
    }

    private function postCheck(Import $import)
    {
        $queue = $this->getItemsToCheck($import);
        if (0 === count($queue)) {
            $isInvalid = $this->isImportQueueInvalid($import);
            $import->setState($isInvalid ? ImportState::IDENTITY_CHECK_FAILED : ImportState::IDENTITY_CHECK_CORRECT);

            $this->entityManager->persist($import);
            $this->entityManager->flush();
        }
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
    private function isImportQueueInvalid(Import $import): bool
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

    /**
     * @param ImportQueue $current
     *
     * @return Beneficiary[]
     */
    private function findInBeneficiaries(ImportQueue $current)
    {
        $founded = [];

        foreach ($current->getContent() as $c) {
            /** @var NationalId[] $ids */
            $ids = $this->entityManager->getRepository(NationalId::class)->findBy([
                'idType' => $c['ID Type'],
                'idNumber' => $c['ID Number'],
            ]);

            foreach ($ids as $id) {
                $founded[] = $this->entityManager->getRepository(Beneficiary::class)->findOneBy(['person' => $id->getPerson()]);
            }
        }

        return $founded;
    }
}
