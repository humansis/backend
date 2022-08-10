<?php


namespace NewApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCacheImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ra:cacheimport:clear')
            ->setDescription('Clear cache of import')
            ->setHelp('Remove all directory which contain data for each step of the import of household by CSV');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            '',
            '============================================================',
            "Clear cache of import",
            '============================================================',
            '',
        ]);

        $dir_root = $this->getContainer()->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data';
        if (is_dir($dir_var)) {
            $this->rrmdir($dir_var);
        }
        $output->writeln([
            'END'
        ]);
    }

    /**
     * @param $src
     */
    private function rrmdir($src)
    {
        $dir = opendir($src);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $full = $src . '/' . $file;
                if (is_dir($full)) {
                    $this->rrmdir($full);
                } else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        rmdir($src);
    }
}
