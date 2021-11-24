<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\ImportQueue;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\IntegrityChecker;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Workflow\ImportQueueTransitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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

    public function __construct(EntityManagerInterface $entityManager, IntegrityChecker $integrityChecker)
    {
        $this->entityManager = $entityManager;
        $this->integrityChecker = $integrityChecker;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import_queue.guard.'.ImportQueueTransitions::VALIDATE => ['guardIfImportQueueIsValid'],
            'workflow.import_queue.guard.'.ImportQueueTransitions::INVALIDATE => ['guardIfImportQueueIsInvalid'],
        ];
    }

    /**
     * @param GuardEvent $guardEvent
     */
    public function guardIfImportQueueIsValid(GuardEvent $guardEvent): void
    {

        /** @var ImportQueue $item */
        $item = $guardEvent->getSubject();
        $violations = $this->integrityChecker->getQueueViolations($item);
        $message = $violations['message'];

        if ($violations['hasViolations']) {
            $message['raw'] = $item->getContent();
            $item->setMessage(json_encode($message));
            $this->entityManager->persist($item);
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Import Queue is invalid', '0'));
        }
    }

    /**
     * @param GuardEvent $guardEvent
     */
    public function guardIfImportQueueIsInvalid(GuardEvent $guardEvent): void
    {
        /** @var ImportQueue $item */
        $item = $guardEvent->getSubject();
        if (is_null($item->getMessage())) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker('Import Queue is valid', '0'));
        }
    }
}
