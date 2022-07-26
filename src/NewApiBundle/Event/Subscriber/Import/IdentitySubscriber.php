<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\Import;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\IdentityChecker;
use NewApiBundle\Component\Import\Messaging\Message\ImportCheck;
use NewApiBundle\Component\Import\Messaging\Message\ItemBatch;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Workflow\ImportTransitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

class IdentitySubscriber implements EventSubscriberInterface
{

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var IdentityChecker
     */
    private $identityChecker;

    /**
     * @var ImportQueueRepository
     */
    private $queueRepository;

    /** @var MessageBusInterface */
    private $messageBus;

    public function __construct(
        EntityManagerInterface $entityManager,
        IdentityChecker        $identityChecker,
        MessageBusInterface    $messageBus,
        ImportQueueRepository  $queueRepository
    ) {
        $this->entityManager = $entityManager;
        $this->identityChecker = $identityChecker;
        $this->messageBus = $messageBus;
        $this->queueRepository = $queueRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import.entered.'.ImportState::IDENTITY_CHECKING => ['fillQueue'],
            'workflow.import.guard.'.ImportTransitions::COMPLETE_IDENTITY => [
                ['guardNoSuspiciousItem', -10],
                ['guardAllItemsChecked', 0],
            ],
            'workflow.import.guard.'.ImportTransitions::FAIL_IDENTITY => [
                ['guardAllItemsChecked', 0],
                ['guardAnySuspiciousItem', 10],
            ],
            'workflow.import.guard.'.ImportTransitions::RESOLVE_IDENTITY_DUPLICITIES => [
                ['guardAllItemsChecked', 0],
                ['guardNoSuspiciousItem', 10],
            ],
        ];
    }

    public function fillQueue(EnteredEvent $event): void
    {
        /** @var Import $import */
        $import = $event->getSubject();

        foreach ($this->queueRepository->findBy([
            'import' => $import,
            'state' => ImportQueueState::VALID,
        ]) as $item) {
            $this->messageBus->dispatch(ItemBatch::checkSingleItemIdentity($item));
        }

        $this->messageBus->dispatch(ImportCheck::checkIdentityComplete($import), [new DelayStamp(5000)]);
    }

    public function guardAllItemsChecked(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();

        if (0 < $this->entityManager->getRepository(ImportQueue::class)->count([
                'import' => $import,
                'state' => ImportQueueState::VALID,
            ])) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('No valid queue items', '0'));
        }
    }

    /**
     * @param GuardEvent $guardEvent
     */
    public function guardAnySuspiciousItem(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();
        $isSuspicious = $this->identityChecker->isImportQueueSuspicious($import);
        if ($isSuspicious === false) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Import has no duplicity suspicious items', '0'));
        }
    }

    /**
     * @param GuardEvent $guardEvent
     */
    public function guardNoSuspiciousItem(GuardEvent $guardEvent): void
    {
        /** @var Import $import */
        $import = $guardEvent->getSubject();
        // dont commit this
        $suspicious = $this->identityChecker->getSuspiciousItems($import);
        foreach ($suspicious as $susp) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Import has duplicity suspicious item #'.$susp->getId(), '0'));
        }
    }

}
