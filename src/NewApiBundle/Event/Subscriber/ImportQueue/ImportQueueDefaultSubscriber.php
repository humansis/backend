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

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import_queue.entered' => ['saveImportQueueState'],
        ];
    }

    /**
     * @param EnteredEvent $enteredEvent
     */
    public function saveImportQueueState(EnteredEvent $enteredEvent): void
    {
        /** @var ImportQueue $import */
        $importQueue = $enteredEvent->getSubject();
        $this->entityManager->flush($importQueue);
    }
}
