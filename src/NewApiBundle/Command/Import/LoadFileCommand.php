<?php
declare(strict_types=1);

namespace NewApiBundle\Command\Import;

use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Import\UploadImportService;
use NewApiBundle\Entity\ImportFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadFileCommand extends AbstractImportQueueCommand
{
    /**
     * @var UploadImportService
     */
    private $uploadImportService;

    public function __construct(ObjectManager $manager, LoggerInterface $importLogger, UploadImportService $uploadImportService)
    {
        parent::__construct($manager, $importLogger);

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
            /** @var ImportFile[] $importFiles */
            $importFiles = $this->manager->getRepository(ImportFile::class)->findBy([
                'isLoaded' => false,
            ]);
        } else {
            /** @var ImportFile[] $importFiles */
            $importFiles = $this->manager->getRepository(ImportFile::class)->findBy([
                'isLoaded' => false,
                'import' => $this->imports,
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
            $this->logger->debug('app:import:integrity affects no imports');
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
