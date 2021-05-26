<?php
declare(strict_types=1);

namespace NewApiBundle\Command\Import;

use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Import\ImportInvalidFileService;
use NewApiBundle\Component\Import\IntegrityChecker;
use NewApiBundle\Entity\Import;
use NewApiBundle\Enum\ImportState;
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

    public function __construct(ObjectManager $manager, IntegrityChecker $integrityChecker, ImportInvalidFileService $importInvalidFileService)
    {
        parent::__construct($manager);

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

        if (is_null($this->import)) {
            $imports = [$this->import];
        } else {
            $imports = $this->manager->getRepository(Import::class)
                ->findBy([
                    'state' => ImportState::INTEGRITY_CHECKING,
                ]);
        }

        $output->writeln([
            "Integrity check",
            count($imports)." imports in queue",

        ]);

        /** @var Import $import */
        foreach ($imports as $import) {
            $this->integrityChecker->check($import);
            $this->importInvalidFileService->generateFile($import);
        }

        $output->writeln('Integrity check completed');
    }

}
