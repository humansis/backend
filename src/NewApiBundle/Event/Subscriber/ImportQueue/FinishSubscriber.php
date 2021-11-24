<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\ImportQueue;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Workflow\ImportQueueTransitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\GuardEvent;

class FinishSubscriber implements EventSubscriberInterface
{

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import_queue.entered.'.ImportQueueTransitions::RESET => ['resetImportQueue'],
        ];
    }

    /**
     * @param EnteredEvent $enteredEvent
     */
    public function resetImportQueue(EnteredEvent $enteredEvent): void
    {
        /** @var ImportQueue $item */
        $item = $enteredEvent->getSubject();
        $item->setIdentityCheckedAt(null);
        $item->setSimilarityCheckedAt(null);
        $this->entityManager->persist($item);
    }
}
