<?php
declare(strict_types=1);

namespace NewApiBundle\Command\Import;

use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Import\ImportInvalidFileService;
use NewApiBundle\Component\Import\IntegrityChecker;
use NewApiBundle\Entity\Import;
use NewApiBundle\Enum\ImportState;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckIntegrityCommand extends AbstractImportQueueCommand
{
    /**
     * @var IntegrityChecker
     */
    private $integrityChecker;

    /**
     * @var ImportInvalidFileService
     */
    private $importInvalidFileService;

    public function __construct(ObjectManager $manager, LoggerInterface $importLogger, IntegrityChecker $integrityChecker, ImportInvalidFileService $importInvalidFileService)
    {
        parent::__construct($manager, $importLogger);

        $this->integrityChecker = $integrityChecker;
        $this->importInvalidFileService = $importInvalidFileService;
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->setName('app:import:integrity')
            ->setDescription('Run integrity check on loaded queue')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        if (empty($this->imports)) {
            $this->imports = $this->manager->getRepository(Import::class)
                ->findBy([
                    'state' => ImportState::INTEGRITY_CHECKING,
                ]);
        }

        if (!empty($this->imports)) {
            $this->logAffectedImports($this->imports, 'app:import:integrity');
        } else {
            $this->logger->debug('app:import:integrity affects no imports');
        }

        $output->writeln([
            "Integrity check of ".count($this->imports)." imports",
        ]);

        /** @var Import $import */
        foreach ($this->imports as $import) {
            $output->writeln($import->getTitle());
            $this->integrityChecker->check($import);
            $invalidFile = $this->importInvalidFileService->generateFile($import);

            if (ImportState::INTEGRITY_CHECK_CORRECT === $import->getState()) {
                $corrects = $import->getImportQueue()->count();
                $this->logImportInfo($import, "Integrity check was successful: $corrects correct records");
            } else {
                $failed = -1;
                $this->logImportInfo($import, "Integrity check wasn't successful: there was $failed incorrect and {$invalidFile->getFilename()} was generated");
            }
        }

        $output->writeln('Integrity check completed');
    }

}
