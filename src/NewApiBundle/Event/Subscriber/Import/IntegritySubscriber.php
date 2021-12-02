<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\Import;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use NewApiBundle\Component\Import\ImportInvalidFileService;
use NewApiBundle\Component\Import\IntegrityChecker;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportFile;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Workflow\ImportTransitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

class IntegritySubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var IntegrityChecker
     */
    private $integrityChecker;

    /**
     * @var EntityRepository|ObjectRepository|ImportQueueRepository
     */
    private $queueRepository;

    /**
     * @var ImportInvalidFileService
     */
    private $importInvalidFileService;

    /**
     * @var int
     */
    private $batchSize;

    public function __construct(
        EntityManagerInterface   $entityManager,
        IntegrityChecker         $integrityChecker,
        ImportInvalidFileService $importInvalidFileService,
        int                      $batchSize
    ) {
        $this->entityManager = $entityManager;
        $this->integrityChecker = $integrityChecker;
        $this->queueRepository = $this->entityManager->getRepository(ImportQueue::class);
        $this->importInvalidFileService = $importInvalidFileService;
        $this->batchSize = $batchSize;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import.guard.'.ImportTransitions::COMPLETE_INTEGRITY => ['guardIfImportHasAnyValidQueueItem'],
            'workflow.import.guard.'.ImportTransitions::FAIL_INTEGRITY => ['guardIfImportHasAnyInvalidQueueItem'],
            // 'workflow.import.entered.'.ImportTransitions::CHECK_INTEGRITY => ['checkIntegrity'],
            'workflow.import.completed.'.ImportTransitions::REDO_INTEGRITY => ['checkIntegrity'],
            'workflow.import.entered.'.ImportTransitions::FAIL_INTEGRITY => ['generateFile'],
        ];
    }

    /**
     * @param CompletedEvent $enteredEvent
     */
    public function checkIntegrity(CompletedEvent $enteredEvent): void
    {
        /** @var Import $import */
        $import = $enteredEvent->getSubject();
        $this->integrityChecker->check($import, $this->batchSize);
    }

    /**
     * @param GuardEvent $guardEvent
     */
    public function guardIfImportHasAnyInvalidQueueItem(GuardEvent $guardEvent): void
    {
        $this->checkImportValidity($guardEvent, false);
    }

    /**
     * @param GuardEvent $guardEvent
     */
    public function guardIfImportHasAnyValidQueueItem(GuardEvent $guardEvent): void
    {
        $this->checkImportValidity($guardEvent, true);
    }

    /**
     * @param Event $event
     */
    public function generateFile(Event $event): void
    {
        /** @var Import $import */
        $import = $event->getSubject();
        $this->importInvalidFileService->generateFile($import);
    }

    /**
     * @param GuardEvent $guardEvent
     * @param bool       $shouldBeValid
     */
    private function checkImportValidity(GuardEvent $guardEvent, bool $shouldBeValid): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();

        if (0 === $this->queueRepository->countItemsToIntegrityCheck($import)) {
            $isInvalid = $this->integrityChecker->isImportQueueInvalid($import);
            if ($isInvalid === $shouldBeValid) {
                $guardEvent->addTransitionBlocker(new TransitionBlocker(sprintf('Integrity check was %svalid', $shouldBeValid ? 'in' : ''), '0'));
            }
        } else {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Integrity check was not completed', '0'));
        }
    }
}
