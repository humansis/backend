<?php
declare(strict_types=1);

namespace NewApiBundle\Command\Import;

use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Import\ImportLoggerTrait;
use NewApiBundle\Component\Import\ImportService;
use NewApiBundle\Entity\Import;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Workflow\WorkflowInterface;

abstract class AbstractImportQueueCommand extends Command
{
    use ImportLoggerTrait;

    /** @var Import[] */
    protected $imports = [];
    /** @var ObjectManager */
    protected $manager;
    /** @var ImportService */
    protected $importService;
    /** @var WorkflowInterface */
    protected $importStateMachine;

    /**
     * AbstractImportQueueCommand constructor.
     *
     * @param ObjectManager                                 $manager
     * @param ImportService                                 $importService
     * @param LoggerInterface                               $importLogger
     * @param WorkflowInterface $importStateMachine
     */
    public function __construct(ObjectManager $manager, ImportService $importService, LoggerInterface $importLogger, WorkflowInterface $importStateMachine)
    {
        parent::__construct();
        $this->manager = $manager;
        $this->logger = $importLogger;
        $this->importService = $importService;
        $this->importStateMachine = $importStateMachine;
    }

    protected function configure()
    {
        $this->addArgument('import', InputArgument::OPTIONAL, 'Filter queue by Import (ID or title)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasArgument('import') && !empty($input->getArgument('import'))) {
            $byId = $this->manager->getRepository(Import::class)->find($input->getArgument('import'));
            $byTitle = $this->manager->getRepository(Import::class)->findOneBy([
                'title' => $input->getArgument('import'),
            ]);
            if ($byId) {
                $this->imports = [$byId];
            }
            if ($byTitle) {
                $this->imports = [$byTitle];
            }
            if (!$byId && !$byTitle) {
                throw new \InvalidArgumentException('Argument Import must be ID or title of existing Import. No such found.');
            }
        }
        return 0;
    }

    /**
     * @param Import[] $imports
     * @param string   $commandType
     */
    protected function logAffectedImports(iterable $imports, string $commandType): void
    {
        $count = 0;
        $importsByCountry = [];
        foreach ($imports as $import) {
            $importsByCountry[$import->getProject()->getIso3()][] = '#'.$import->getId()."|".$import->getState();
            $count++;
        }
        $countryList = [];
        foreach ($importsByCountry as $country => $ids) {
            $countryList[] = $country.'('.implode(', ', $ids).')';
        }
        $this->logger->info("$commandType will affect $count imports: ".implode(' ', $countryList));
    }

    protected function tryTransitions(Import $import, array $transitions): void
    {
        foreach ($transitions as $transition) {
            if ($this->importStateMachine->can($import, $transition)) {
                $this->logImportInfo($import, "is going to '$transition'");
                $this->importStateMachine->apply($import, $transition);
                return;
            } else {
                $this->logImportTransitionConstraints($import, $transition);
            }
        }
    }
}
