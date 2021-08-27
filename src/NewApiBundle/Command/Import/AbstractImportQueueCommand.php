<?php
declare(strict_types=1);

namespace NewApiBundle\Command\Import;

use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Import\ImportLoggerTrait;
use NewApiBundle\Component\Import\ImportService;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractImportQueueCommand extends Command
{
    use ImportLoggerTrait;

    /** @var Import[] */
    protected $imports = [];
    /** @var ObjectManager */
    protected $manager;
    /** @var ImportService */
    protected $importService;
    /** @var int */
    protected $batchSize = 200;

    /**
     * AbstractImportQueueCommand constructor.
     *
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager, ImportService $importService, LoggerInterface $importLogger)
    {
        parent::__construct();
        $this->manager = $manager;
        $this->logger = $importLogger;
        $this->importService = $importService;
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
            $importsByCountry[$import->getProject()->getIso3()][] = '#'.$import->getId();
            $count++;
        }
        $countryList = [];
        foreach ($importsByCountry as $country => $ids) {
            $countryList[] = $country.'('.implode(', ', $ids).')';
        }
        $this->logger->info("$commandType will affect $count imports: ".implode(' ', $countryList));
    }

    protected function logImportInfo(Import $import, string $message): void
    {
        $this->logger->info("[Import #{$import->getId()}] ({$import->getTitle()}) $message");
    }

    protected function logImportDebug(Import $import, string $message): void
    {
        $this->logger->debug("[Import #{$import->getId()}] ({$import->getTitle()}) $message");
    }

    protected function logImportError(Import $import, string $message): void
    {
        $this->logger->error("[Import #{$import->getId()}] ({$import->getTitle()}) $message");
    }

}
