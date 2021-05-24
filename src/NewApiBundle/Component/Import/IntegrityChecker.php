<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\Integrity;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class IntegrityChecker
{
    /** @var ValidatorInterface */
    private $validator;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(ValidatorInterface $validator, EntityManagerInterface $entityManager)
    {
        $this->validator = $validator;
        $this->entityManager = $entityManager;
    }

    public function check(Import $import): ConstraintViolationListInterface
    {
        if (ImportState::INTEGRITY_CHECKING !== $import->getState()) {
            throw new \BadMethodCallException('Unable to execute checker. Import is not ready to integrity check.');
        }

        foreach ($this->getItemsToCheck($import) as $i => $item) {
            $this->checkOne($item);

            if ($i % 500 === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();

        $queue = $this->getItemsToCheck($import);
        if (0 === count($queue)) {
            $isInvalid = $this->isImportQueueInvalid($import);
            $import->setState($isInvalid ? ImportState::INTEGRITY_CHECK_FAILED : ImportState::INTEGRITY_CHECK_CORRECT);

            $this->entityManager->persist($import);
            $this->entityManager->flush();
        }
    }

    protected function checkOne(ImportQueue $item)
    {
        $iso3 = $item->getImport()->getProject()->getIso3();
        $content = $item->getContent();

        $headContent = $content[0];
        $memberContents = array_slice($content, 1);

        $violationList = new ConstraintViolationList();
        $violationList->addAll(
            $this->validator->validate(new Integrity\HouseholdHead($headContent, $iso3, $this->entityManager))
        );

        foreach ($memberContents as $memberContent) {
            $violationList->addAll(
                $this->validator->validate(new Integrity\HouseholdMember($memberContent, $iso3, $this->entityManager))
            );
        }

        if ($violationList->count() > 1) {
            $message = [];
            foreach ($violationList as $violation) {
                $message[] = ['column' => $violation->getPropertyPath(), 'violation' => $violation->getMessage()];
            }

            $item->setMessage(json_encode($message));
            $item->setState(ImportQueueState::INVALID);
        } else {
            $item->setState(ImportQueueState::VALID);
        }

        $this->entityManager->persist($item);
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
            ->findBy(['import' => $import, 'state' => ImportQueueState::INVALID]);

        return count($queue) > 0;
    }
}
