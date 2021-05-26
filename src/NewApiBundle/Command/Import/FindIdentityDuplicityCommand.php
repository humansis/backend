<?php
declare(strict_types=1);

namespace NewApiBundle\Command\Import;

use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Import\IdentityChecker;
use NewApiBundle\Entity\Import;
use NewApiBundle\Enum\ImportState;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindIdentityDuplicityCommand extends AbstractImportQueueCommand
{
    /**
     * @var IdentityChecker
     */
    private $identityChecker;

    public function __construct(ObjectManager $manager, IdentityChecker $identityChecker)
    {
        parent::__construct($manager);

        $this->identityChecker = $identityChecker;
    }

    protected function configure()
    {
        $this
            ->setName('app:import:identity')
            ->setDescription('Run identity duplicity check on import')
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
                    'state' => ImportState::IDENTITY_CHECKING,
                ]);
        }

        $output->writeln([
            "Identity check",
            count($imports)." imports in queue",

        ]);

        /** @var Import $import */
        foreach ($imports as $import) {
            $this->identityChecker->check($import);
        }

        $output->writeln('Identity check completed');
    }
}
