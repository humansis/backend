<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Command;

use Doctrine\Persistence\ObjectManager;
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

class IntegrityCheckCommand extends AbstractQueueCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('app:import:integrity')
            ->setDescription('Run integrity check on loaded queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        if (empty($this->imports)) {
            $this->imports = $this->manager->getRepository(Import::class)
                ->findBy([
                    'state' => State::INTEGRITY_CHECKING,
                ]);
        }

        if (!empty($this->imports)) {
            $this->logAffectedImports($this->imports, 'app:import:integrity');
        } else {
            $this->logger->debug('app:import:integrity affects no imports');
        }

        $output->write($this->getName()." checking integrity of ".count($this->imports)." imports ");

        /** @var Import $import */
        foreach ($this->imports as $import) {
            $output->writeln($import->getTitle());

            if (in_array($import->getState(), [State::INTEGRITY_CHECK_CORRECT, State::INTEGRITY_CHECK_FAILED])) {
                $this->logImportDebug($import, "Import already processed");
                continue;
            }

            try {
                $this->tryTransitions($import, [
                    Transitions::REDO_INTEGRITY,
                    Transitions::FAIL_INTEGRITY,
                    Transitions::COMPLETE_INTEGRITY,
                ]);
                $this->manager->flush();

                $statistics = $this->importService->getStatistics($import);
                if (State::INTEGRITY_CHECK_CORRECT === $import->getState()) {
                    $this->logImportInfo($import, "Integrity check found {$statistics->getAmountIntegrityFailed()} integrity errors");
                    $this->logImportInfo($import, "Integrity check was successful: {$statistics->getAmountIntegrityCorrect()} correct records from all ".$statistics->getTotalEntries());
                } else {
                    $this->logImportInfo($import, "Integrity check found {$statistics->getAmountIntegrityFailed()} integrity errors");
                }
            } catch (Throwable $e) {
                $this->logImportError($import, 'Unknown Exception in integrity check occurred. Exception message: '.$e->getMessage());
            }
        }

        $output->writeln('Done');

        return 0;
    }

}
