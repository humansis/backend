<?php declare(strict_types=1);

namespace NewApiBundle\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\ImportLoggerTrait;
use NewApiBundle\Entity\Import;
use NewApiBundle\Enum\ImportState;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Workflow\Event\GuardEvent;

class ImportWorkflowSubscriber implements EventSubscriberInterface
{
    use ImportLoggerTrait;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param GuardEvent $event
     */
    public function guardImporting(GuardEvent $event): void
    {
        /** @var Import $import */
        $import = $event->getSubject();

        if (!$this->em->getRepository(Import::class)
            ->isCountryFreeFromImporting($import, $import->getProject()->getIso3())) {
            throw new BadRequestHttpException("There can be only one finishing import in country in single time.");
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.import.guard.Importing' => ['guardImporting'],
        ];
    }
}
