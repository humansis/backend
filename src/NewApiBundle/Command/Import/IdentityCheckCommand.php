<?php
declare(strict_types=1);

namespace NewApiBundle\Command\Import;

use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Import\IdentityChecker;
use NewApiBundle\Component\Import\ImportService;
use NewApiBundle\Entity\Import;
use NewApiBundle\Enum\ImportState;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class IdentityCheckCommand extends AbstractImportQueueCommand
{
    public function __construct(ObjectManager $manager, ImportService $importService, LoggerInterface $importLogger)
    {
        parent::__construct($manager, $importService, $importLogger);
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->setName('app:import:identity')
            ->setDescription('Run identity duplicity check on import')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        if (empty($this->imports)) {
            $this->imports = $this->manager->getRepository(Import::class)
                ->findBy([
                    'state' => ImportState::IDENTITY_CHECKING,
                ]);
        }

        if (!empty($this->imports)) {
            $this->logAffectedImports($this->imports, 'app:import:identity');
        } else {
            $this->logger->debug('app:import:integrity affects no imports');
        }

        $output->write($this->getName()." finding identical duplicities in ".count($this->imports)." imports ");

        /** @var Import $import */
        foreach ($this->imports as $import) {
            $output->writeln($import->getTitle());

            try {
                $this->importService->checkIdentity($import, $this->batchSize);

                if (ImportState::IDENTITY_CHECK_CORRECT === $import->getState()) {
                    $this->logImportDebug($import, "Identity check found no duplicities");
                } else {
                    $statistics = $this->importService->getStatistics($import);
                    $this->logImportInfo($import, "Identity check found {$statistics->getAmountDuplicities()} duplicities");
                }
            } catch (Throwable $e) {
                $this->logImportError($import, 'Unknown Exception in identity check occurred. Exception message: '.$e->getMessage());
            }
        }

        $output->writeln('Done');
    }
}
