<?php

declare(strict_types=1);

namespace Command\Import;

use Doctrine\Persistence\ObjectManager;
use Component\Import\ImportLoggerTrait;
use Component\Import\ImportService;
use Entity\Import;
use InvalidArgumentException;
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

    /**
     * AbstractImportQueueCommand constructor.
     *
     * @param ObjectManager $manager
     * @param ImportService $importService
     * @param LoggerInterface $importLogger
     * @param WorkflowInterface $importStateMachine
     */
    public function __construct(
        protected ObjectManager $manager,
        protected ImportService $importService,
        LoggerInterface $importLogger,
        protected WorkflowInterface $importStateMachine
    ) {
        parent::__construct();
        $this->logger = $importLogger;
    }

    protected function configure()
    {
        $this->addArgument('import', InputArgument::OPTIONAL, 'Filter queue by Import (ID or title)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
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
                throw new InvalidArgumentException(
                    'Argument Import must be ID or title of existing Import. No such found.'
                );
            }
        }

        return 0;
    }

    /**
     * @param Import[] $imports
     */
    protected function logAffectedImports(iterable $imports, string $commandType): void
    {
        $count = 0;
        $importsByCountry = [];
        foreach ($imports as $import) {
            $importsByCountry[$import->getCountryIso3()][] = '#' . $import->getId() . "|" . $import->getState();
            $count++;
        }
        $countryList = [];
        foreach ($importsByCountry as $country => $ids) {
            $countryList[] = $country . '(' . implode(', ', $ids) . ')';
        }
        $this->logger->info("$commandType will affect $count imports: " . implode(' ', $countryList));
    }
}
