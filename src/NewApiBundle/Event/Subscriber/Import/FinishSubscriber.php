<?php declare(strict_types=1);

namespace NewApiBundle\Event\Subscriber\Import;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use NewApiBundle\Component\Import\ImportFinisher;
use NewApiBundle\Entity\Import;
use NewApiBundle\Workflow\ImportTransitions;
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

    public function __construct(EntityManagerInterface $entityManager, ImportFinisher $importFinisher)
    {
        $this->importFinisher = $importFinisher;
        $this->entityManager = $entityManager;
    }

    /**
     * @param GuardEvent $event
     */
    public function guardImport(GuardEvent $event): void
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
    public function enteredImporting(EnteredEvent $enteredEvent): void
    {
        /** @var Import $import */
        $import = $enteredEvent->getSubject();
        $this->importFinisher->import($import);
    }

    /**
     * @param EnteredEvent $enteredEvent
     */
    public function enteredFinish(EnteredEvent $enteredEvent): void
    {
        /** @var Import $import */
        $import = $enteredEvent->getSubject();
        $this->importFinisher->finish($import);
    }

    /**
     * @param EnteredEvent $enteredEvent
     */
    public function enteredReset(EnteredEvent $enteredEvent): void
    {
        /** @var Import $import */
        $import = $enteredEvent->getSubject();
        $this->importFinisher->resetImport($import);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import.guard.'.ImportTransitions::IMPORT => ['guardImport'],
            'workflow.import.entered.'.ImportTransitions::IMPORT => ['enteredImporting'],
            'workflow.import.entered.'.ImportTransitions::FINISH => ['enteredFinish'],
            'workflow.import.entered.'.ImportTransitions::RESET => ['enteredReset'],
        ];
    }
}
