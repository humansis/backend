<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\ImportQueue;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Workflow\ImportQueueTransitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;

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

    /**
     * @param EnteredEvent $enteredEvent
     */
    public function enteredReset(EnteredEvent $enteredEvent): void
    {
        /** @var ImportQueue $item */
        $item = $enteredEvent->getSubject();
        $item->setIdentityCheckedAt(null);
        $item->setSimilarityCheckedAt(null);
        $this->entityManager->persist($item);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.importQueue.entered.'.ImportQueueTransitions::RESET => ['enteredReset'],
        ];
    }
}
