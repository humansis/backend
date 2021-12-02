<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\Import;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use NewApiBundle\Component\Import\ImportFinisher;
use NewApiBundle\Component\Import\ImportReset;
use NewApiBundle\Entity\Import;
use NewApiBundle\Workflow\ImportTransitions;
use NewApiBundle\Workflow\WorkflowTool;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

class FinishSubscriber implements EventSubscriberInterface
{
    /**
     * @var ImportFinisher
     */
    private $importFinisher;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ImportReset
     */
    private $importReset;

    public function __construct(EntityManagerInterface $entityManager, ImportFinisher $importFinisher, ImportReset $importReset)
    {
        $this->importFinisher = $importFinisher;
        $this->entityManager = $entityManager;
        $this->importReset = $importReset;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import.guard.'.ImportTransitions::IMPORT => ['guardIfThereIsOnlyOneFinishingImport'],
            'workflow.import.entered.'.ImportTransitions::IMPORT => ['doImport'],
            'workflow.import.entered.'.ImportTransitions::FINISH => ['finishImport'],
            'workflow.import.entered.'.ImportTransitions::RESET => ['resetImport'],
        ];
    }

    /**
     * @param GuardEvent $event
     */
    public function guardIfThereIsOnlyOneFinishingImport(GuardEvent $event): void
    {
        /** @var Import $import */
        $import = $event->getSubject();

        if (!$this->entityManager->getRepository(Import::class)
            ->isCountryFreeFromImporting($import, $import->getProject()->getIso3())) {
            $event->addTransitionBlocker(new TransitionBlocker('There can be only one finishing import in country in single time.', '0'));
        }
    }

    /**
     * @param EnteredEvent $enteredEvent
     *
     * @throws EntityNotFoundException
     */
    public function doImport(EnteredEvent $enteredEvent): void
    {
        /** @var Import $import */
        $import = $enteredEvent->getSubject();
        $this->importFinisher->import($import);
    }

    /**
     * @param EnteredEvent $enteredEvent
     */
    public function finishImport(EnteredEvent $enteredEvent): void
    {
        /** @var Import $import */
        $import = $enteredEvent->getSubject();
        $this->importFinisher->finish($import);
    }

    /**
     * @param EnteredEvent $enteredEvent
     */
    public function resetImport(EnteredEvent $enteredEvent): void
    {
        /** @var Import $import */
        $import = $enteredEvent->getSubject();
        $this->importReset->reset($import);
    }
}
