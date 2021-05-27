<?php
declare(strict_types=1);

namespace NewApiBundle\Command\Import;

use BeneficiaryBundle\Entity\Person;
use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Import\IdentityChecker;
use NewApiBundle\Entity\Import;
use NewApiBundle\Enum\ImportState;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindIdentityDuplicityCommand extends AbstractImportQueueCommand
{
    /**
     * @var IdentityChecker
     */
    private $identityChecker;

    public function __construct(ObjectManager $manager, LoggerInterface $importLogger,
                                IdentityChecker $identityChecker
    )
    {
        parent::__construct($manager, $importLogger);
        $this->identityChecker = $identityChecker;
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

        $output->writeln([
            "Identity check of ".count($this->imports)." imports",
        ]);

        /** @var Import $import */
        foreach ($this->imports as $import) {
            $output->writeln($import->getTitle());
            $this->identityChecker->check($import);

            if (ImportState::IDENTITY_CHECK_CORRECT === $import->getState()) {
                $this->logImportDebug($import, "Identity check found no duplicities");
            } else {
                $duplicities = -1;
                $this->logImportInfo($import, "Identity check found $duplicities duplicities");
            }
        }

        $output->writeln('Identity check completed');
    }
}
