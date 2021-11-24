<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\Import;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\Import;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;

class ImportWorkflowSubscriber implements EventSubscriberInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import.entered' => ['saveImportState'],
        ];
    }

    /**
     * @param EnteredEvent $enteredEvent
     */
    public function saveImportState(EnteredEvent $enteredEvent): void
    {
        /** @var Import $import */
        $import = $enteredEvent->getSubject();
        $this->entityManager->flush($import);
    }
}
