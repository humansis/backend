<?php
declare(strict_types=1);

namespace NewApiBundle\Command\Import;

use Symfony\Component\Console\Command\Command;

class LoadFileCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('app:import:load')
            ->setDescription('Load import files into database')
        ;
    }
}
