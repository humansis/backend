<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\Import;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use NewApiBundle\Component\Import\SimilarityChecker;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Workflow\ImportTransitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

class SimilaritySubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var SimilarityChecker
     */
    private $similarityChecker;

    /**
     * @var EntityRepository|ObjectRepository|ImportQueueRepository
     */
    private $queueRepository;

    /**
     * @var int
     */
    private $batchSize;

    public function __construct(EntityManagerInterface $entityManager, SimilarityChecker $similarityChecker, int $batchSize)
    {
        $this->entityManager = $entityManager;
        $this->similarityChecker = $similarityChecker;
        $this->queueRepository = $this->entityManager->getRepository(ImportQueue::class);
        $this->batchSize = $batchSize;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import.guard.'.ImportTransitions::COMPLETE_SIMILARITY => ['guardIfImportHasNotSuspiciousItems'],
            'workflow.import.guard.'.ImportTransitions::FAIL_SIMILARITY => ['guardIfImportHasSuspiciousItems'],
            'workflow.import.guard.'.ImportTransitions::RESOLVE_SIMILARITY_DUPLICITIES => ['guardIfImportHasNotSuspiciousItems'],
            'workflow.import.entered.'.ImportTransitions::COMPLETE_SIMILARITY => ['completeSimilarity'],
            'workflow.import.entered.'.ImportTransitions::CHECK_SIMILARITY => ['checkSimilarity'],
        ];
    }

    /**
     * @param EnteredEvent $enteredEvent
     */
    public function completeSimilarity(EnteredEvent $enteredEvent): void
    {
        /** @var Import $import */
        $import = $enteredEvent->getSubject();
        $this->similarityChecker->postCheck($import);
    }

    /**
     * @param GuardEvent $guardEvent
     */
    public function guardIfImportHasSuspiciousItems(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();
        if ($this->checkImportSimilarity($import) === false) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Import has not any suspicious items', '0'));
        }
    }

    /**
     * @param GuardEvent $guardEvent
     */
    public function guardIfImportHasNotSuspiciousItems(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();
        if ($this->checkImportSimilarity($import) === true) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Import has suspicious items', '0'));
        }
    }

    /**
     * @param EnteredEvent $enteredEvent
     */
    public function checkSimilarity(EnteredEvent $enteredEvent): void
    {
        /** @var Import $import */
        $import = $enteredEvent->getSubject();
        $this->similarityChecker->check($import, $this->batchSize);
    }

    /**
     * @param Import $import
     *
     * @return bool
     */
    private function checkImportSimilarity(Import $import): bool
    {
        return count($this->queueRepository->getSuspiciousItemsToUserCheck($import)) > 0;
    }
}
