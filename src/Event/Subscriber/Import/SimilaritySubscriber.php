<?php declare(strict_types=1);

namespace Event\Subscriber\Import;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Component\Import\Messaging\Message\ImportCheck;
use Component\Import\Messaging\Message\ItemBatch;
use Component\Import\SimilarityChecker;
use Entity\Import;
use Enum\ImportQueueState;
use Enum\ImportState;
use Repository\ImportQueueRepository;
use Workflow\ImportTransitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

class SimilaritySubscriber implements EventSubscriberInterface
{

    public const GUARD_CODE_NOT_COMPLETE = '7135e866-fb87-4e4f-bfa6-c42f48cfebc9';


    /**
     * @var SimilarityChecker
     */
    private $similarityChecker;

    /**
     * @var EntityRepository|ObjectRepository|ImportQueueRepository
     */
    private $queueRepository;

    /** @var MessageBusInterface */
    private $messageBus;


    public function __construct(
        SimilarityChecker      $similarityChecker,
        ImportQueueRepository  $queueRepository,
        MessageBusInterface    $messageBus
    ) {
        $this->similarityChecker = $similarityChecker;
        $this->queueRepository = $queueRepository;
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import.entered.'.ImportState::SIMILARITY_CHECKING => ['checkSimilarity'],
            'workflow.import.guard.'.ImportTransitions::COMPLETE_SIMILARITY => [
                ['guardNothingLeft', -10],
                ['guardIfImportHasNotSuspiciousItems', 0],
            ],
            'workflow.import.guard.'.ImportTransitions::FAIL_SIMILARITY => [
                ['guardNothingLeft', -10],
                ['guardIfImportHasSuspiciousItems', 0],
            ],
            'workflow.import.guard.'.ImportTransitions::REDO_SIMILARITY => ['guardSomeItemsLeft'],
            'workflow.import.guard.'.ImportTransitions::RESOLVE_SIMILARITY_DUPLICITIES => ['guardIfImportHasNotSuspiciousItems'],
            'workflow.import.entered.'.ImportTransitions::COMPLETE_SIMILARITY => ['completeSimilarity'],
            'workflow.import.completed.'.ImportTransitions::REDO_SIMILARITY => ['checkSimilarityAgain'],
        ];
    }

    public function checkSimilarity(EnteredEvent $event): void
    {
        /** @var Import $import */
        $import = $event->getSubject();
        $this->fillQueue($import);
    }

    public function guardNothingLeft(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();

        $isComplete = (0 === $this->queueRepository->countItemsToSimilarityCheck($import));

        if (!$isComplete) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Similarity check was not completed', static::GUARD_CODE_NOT_COMPLETE));
        }
    }

    public function guardSomeItemsLeft(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();

        $isComplete = (0 === $this->queueRepository->countItemsToSimilarityCheck($import));

        if ($isComplete) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Similarity check was completed', '0'));
        }
    }

    /**
     * @param EnteredEvent $enteredEvent
     */
    public function completeSimilarity(EnteredEvent $enteredEvent): void
    {
        /** @var Import $import */
        $import = $enteredEvent->getSubject();
        // $this->similarityChecker->postCheck($import);
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
    public function checkSimilarityAgain(CompletedEvent $enteredEvent): void
    {
        /** @var Import $import */
        $import = $enteredEvent->getSubject();
        $this->fillQueue($import);
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

    private function fillQueue(Import $import)
    {
        foreach ($this->queueRepository->findBy([
            'import' => $import,
            'state' => [ImportQueueState::NEW],
        ]) as $item) {
            $this->messageBus->dispatch(ItemBatch::checkSingleItemSimilarity($item));
        }
        $this->messageBus->dispatch(ImportCheck::checkSimilarityComplete($import), [new DelayStamp(5000)]);
    }
}
