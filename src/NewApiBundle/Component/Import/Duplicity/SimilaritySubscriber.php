<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Duplicity;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use NewApiBundle\Component\Import\Entity\Import;
use NewApiBundle\Component\Import\Entity\Queue;
use NewApiBundle\Repository\QueueRepository;
use NewApiBundle\Component\Import\Enum\Transitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
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
     * @var EntityRepository|ObjectRepository|QueueRepository
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
        $this->queueRepository = $this->entityManager->getRepository(Queue::class);
        $this->batchSize = $batchSize;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import.guard.'.Transitions::COMPLETE_SIMILARITY => ['guardIfImportHasNotSuspiciousItems'],
            'workflow.import.guard.'.Transitions::FAIL_SIMILARITY => ['guardIfImportHasSuspiciousItems'],
            'workflow.import.guard.'.Transitions::RESOLVE_SIMILARITY_DUPLICITIES => ['guardIfImportHasNotSuspiciousItems'],
            'workflow.import.entered.'.Transitions::COMPLETE_SIMILARITY => ['completeSimilarity'],
            'workflow.import.completed.'.Transitions::REDO_SIMILARITY => ['checkSimilarity'],
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
     * @param CompletedEvent $enteredEvent
     */
    public function checkSimilarity(CompletedEvent $enteredEvent): void
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
