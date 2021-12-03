<?php
declare(strict_types=1);

namespace NewApiBundle\Command\Import;

use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Import\ImportInvalidFileService;
use NewApiBundle\Component\Import\ImportService;
use NewApiBundle\Entity\Import;
use NewApiBundle\Repository\ImportRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanCommand extends AbstractImportQueueCommand
{
    /**
     * @var ImportInvalidFileService
     */
    private $importInvalidFileService;

    public function __construct(ObjectManager $manager, ImportService $importService, LoggerInterface $importLogger, ImportInvalidFileService $importInvalidFileService)
    {
        parent::__construct($manager, $importService, $importLogger, null);

        $this->importInvalidFileService = $importInvalidFileService;
    }


    protected function configure()
    {
        parent::configure();
        $this
            ->setName('app:import:clean')
            ->setDescription('Clean data of finished import')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        if (empty($this->imports)) {
            /** @var ImportRepository $importRepository */
            $importRepository = $this->manager->getRepository(Import::class);

            $this->imports = $importRepository->getFinishedWithInvalidFiles();
        }

        if (!empty($this->imports)) {
            $this->logAffectedImports($this->imports, 'app:import:clean');
        } else {
            $this->logger->debug('app:import:clean affects no imports');
            return 0;
        }

        $output->writeln([
            "Clean of ".count($this->imports)." imports",
        ]);

        foreach ($this->imports as $import) {
            $this->importInvalidFileService->removeInvalidFiles($import);

            $this->logImportInfo($import, 'Invalid files removed');
        }

        $output->writeln('Clean completed');

        //TODO what data should we keep for canceled imports?

        return 0;
    }
}
