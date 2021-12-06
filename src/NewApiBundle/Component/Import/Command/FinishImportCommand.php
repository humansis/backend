<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Command;

use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Import\ImportFinisher;
use NewApiBundle\Component\Import\Service\ImportService;
use NewApiBundle\Component\Import\Entity\Import;
use NewApiBundle\Component\Import\Enum\State;
use NewApiBundle\Component\Import\Enum\Transitions;
use NewApiBundle\Workflow\WorkflowTool;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Throwable;

class FinishImportCommand extends AbstractQueueCommand
{
    /** @var ImportFinisher */
    private $importFinisher;

    public function __construct(
        ObjectManager     $manager,
        ImportService     $importService,
        LoggerInterface   $importLogger,
        WorkflowInterface $importStateMachine,
        ImportFinisher    $importFinisher
    ) {
        parent::__construct($manager, $importService, $importLogger, $importStateMachine);
        $this->importFinisher = $importFinisher;
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->setName('app:import:finish')
            ->setDescription('Save finished imports into DB');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        if (empty($this->imports)) {
            $this->imports = $this->manager->getRepository(Import::class)
                ->findBy([
                    'state' => State::IMPORTING,
                ]);
        }

        if (!empty($this->imports)) {
            $this->logAffectedImports($this->imports, 'app:import:finish');
        } else {
            $this->logger->debug('app:import:finish affects no imports');
        }

        $output->write($this->getName()." finishing ".count($this->imports)." imports ");

        /** @var Import $import */
        foreach ($this->imports as $import) {
            try {
                $this->importFinisher->import($import);
                $this->manager->flush();
                $this->logImportDebug($import, "Finished");
            } catch (Throwable $e) {
                $this->logImportError($import, 'Unknown Exception in finishing occurred. Exception message: '.$e->getMessage());
            }
        }
        $output->writeln('Done');

        return 0;
    }
}
