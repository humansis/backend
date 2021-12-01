<?php
declare(strict_types=1);

namespace NewApiBundle\Command\Import;

use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Import\ImportService;
use NewApiBundle\Entity\Import;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Workflow\ImportTransitions;
use NewApiBundle\Workflow\WorkflowTool;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Throwable;

class IdentityCheckCommand extends AbstractImportQueueCommand
{
    /**
     * @var WorkflowInterface
     */
    private $importStateMachine;

    public function __construct(
        ObjectManager     $manager,
        ImportService     $importService,
        LoggerInterface   $importLogger,
        WorkflowInterface $importStateMachine
    ) {
        parent::__construct($manager, $importService, $importLogger);
        $this->importStateMachine = $importStateMachine;
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->setName('app:import:identity')
            ->setDescription('Run identity duplicity check on import');
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
                WorkflowTool::checkAndApply($this->importStateMachine, $import,
                    [ImportTransitions::REDO_IDENTITY, ImportTransitions::FAIL_IDENTITY, ImportTransitions::COMPLETE_IDENTITY]);
                $this->manager->flush();

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

        return 0;
    }
}
