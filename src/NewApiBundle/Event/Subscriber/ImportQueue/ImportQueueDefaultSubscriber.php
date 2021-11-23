<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\ImportQueue;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\ImportQueue;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;

class ImportQueueDefaultSubscriber implements EventSubscriberInterface
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
    public function onEntered(EnteredEvent $enteredEvent): void
    {
        /** @var ImportQueue $import */
        $import = $enteredEvent->getSubject();

        // Save entity state
        $this->entityManager->flush($import);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.importQueue.entered' => ['onEntered'],
        ];
    }
}
