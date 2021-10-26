<?php declare(strict_types=1);

namespace NewApiBundle\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\ImportInvalidFileService;
use NewApiBundle\Entity\Import;
use NewApiBundle\Workflow\ImportTransitions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

class ImportWorkflowSubscriber implements EventSubscriberInterface
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var ImportInvalidFileService */
    private $importInvalidFileService;

    public function __construct(EntityManagerInterface $em, ImportInvalidFileService $importInvalidFileService)
    {
        $this->em = $em;
        $this->importInvalidFileService = $importInvalidFileService;
    }

    /**
     * @param GuardEvent $event
     */
    public function guardImport(GuardEvent $event): void
    {
        /** @var Import $import */
        $import = $event->getSubject();

        if (!$this->em->getRepository(Import::class)
            ->isCountryFreeFromImporting($import, $import->getProject()->getIso3())) {

            // TODO this should be catch with original message
            $event->addTransitionBlocker(new TransitionBlocker('There can be only one finishing import in country in single time.', '0'));
        }
    }

    /**
     * @param GuardEvent $event
     */
    public function guardIntegrityInvalidFile(GuardEvent $event): void
    {
        /** @var Import $import */
        $import = $event->getSubject();

        $this->importInvalidFileService->generateFile($import);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import.guard.'.ImportTransitions::FAIL_INTEGRITY => ['guardIntegrityInvalidFile'],
            'workflow.import.guard.'.ImportTransitions::IMPORT => ['guardImport'],
        ];
    }
}
