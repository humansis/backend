<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Command;

use NewApiBundle\Component\Import\Entity\Import;
use NewApiBundle\Component\Import\Enum\State;
use NewApiBundle\Component\Import\Enum\Transitions;
use NewApiBundle\Workflow\WorkflowTool;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Throwable;

class IdentityCheckCommand extends AbstractQueueCommand
{
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
                    'state' => State::IDENTITY_CHECKING,
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
            if (in_array($import->getState(), [State::IDENTITY_CHECK_CORRECT, State::IDENTITY_CHECK_FAILED])) {
                $this->logImportDebug($import, "Import already processed");
                continue;
            }

            try {
                $this->tryTransitions($import, [
                    Transitions::REDO_IDENTITY,
                    Transitions::FAIL_IDENTITY,
                    Transitions::COMPLETE_IDENTITY
                ]);
                $this->manager->flush();

                if (State::IDENTITY_CHECK_CORRECT === $import->getState()) {
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
