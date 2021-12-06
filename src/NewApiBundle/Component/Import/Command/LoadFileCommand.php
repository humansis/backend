<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Command;

use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Import\Service\ImportService;
use NewApiBundle\Component\Import\Service\UploadImportService;
use NewApiBundle\Component\Import\Entity\File;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class LoadFileCommand extends AbstractQueueCommand
{
    /**
     * @var UploadImportService
     */
    private $uploadImportService;

    public function __construct(ObjectManager $manager, ImportService $importService, LoggerInterface $importLogger, UploadImportService $uploadImportService,
                               WorkflowInterface $importStateMachine
    )
    {
        parent::__construct($manager, $importService, $importLogger, $importStateMachine);

        $this->uploadImportService = $uploadImportService;
    }

    protected function configure()
    {
        $this
            ->setName('app:import:load')
            ->setDescription('Load import files into database')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        if (empty($this->imports)) {
            /** @var File[] $importFiles */
            $importFiles = $this->manager->getRepository(File::class)->findBy([
                'isLoaded' => false,
                'structureViolations' => null,
            ]);
        } else {
            /** @var File[] $importFiles */
            $importFiles = $this->manager->getRepository(File::class)->findBy([
                'isLoaded' => false,
                'import' => $this->imports,
                'structureViolations' => null,
            ]);
        }

        $affectedImports = [];

        foreach ($importFiles as $importFile) {
            $affectedImports[] = $importFile->getImport();
        }

        $affectedImports = array_unique($affectedImports, SORT_REGULAR);

        if (!empty($affectedImports)) {
            $this->logAffectedImports($affectedImports, 'app:import:load');
        } else {
            $this->logger->debug('app:import:load affects no imports');
        }

        $output->writeln([
            "Loading import files to database",
        ]);

        foreach ($importFiles as $importFile) {
            try {
                $this->uploadImportService->load($importFile);

                $this->logImportInfo($importFile->getImport(), 'Import file '.$importFile->getFilename().' (ID '.$importFile->getId().') was loaded.');
            } catch (\Exception $e) {
                $this->logImportError($importFile->getImport(), 'Import file '.$importFile->getFilename().' (ID '.$importFile->getId().') could not be loaded. Exception message: '.$e->getMessage().'.');
                //TODO What should happen to file which could not be loaded?
            }
        }

        $output->writeln('Loading import files to database completed');

        return 0;
    }
}
