<?php

namespace CommonBundle\Command;

use CommonBundle\DataFixtures\LocationFixtures;
use CommonBundle\Utils\LocationImporter;
use CommonBundle\Utils\LocationService;
use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class AdmXML2DBCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:adm:upload')
            ->setDescription('Interactive import ADM into DB')
            ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var LocationService $locationService */
        $locationService = $this->getContainer()->get('location_service');

        $countryCode = $this->getHelper('question')->ask($input, $output, new ChoiceQuestion(
            'Which file do you want import? ',
            $this->getADMFiles()
        ));
        $countryFile = $this->getADMFiles()[$countryCode];
        echo "Importing file $countryFile\n";

        $importer = new LocationImporter($this->getContainer()->get('doctrine.orm.default_entity_manager'), $countryFile);

        $progressBar = new ProgressBar($output, $importer->getCount());
        $progressBar->start();

        foreach ($importer->importLocations() as $importStatus) {
            $progressBar->advance();

            if (isset($importStatus['inconsistent'])) {
                $oldName = $importStatus['inconsistent']['old'];
                $newName = $importStatus['inconsistent']['new'];
                echo "Duplicity code but name inconsistency, old=$oldName, new=$newName\n";
            }
        }

        $progressBar->finish();
        echo "\nDONE, imported {$importer->getImportedLocations()}, omitted {$importer->getOmittedLocations()}\n";

        return 0;
    }

    private function getADMFiles(): array
    {
        $directory = __DIR__.'/../Resources/locations';

        $choices = [];
        foreach (scandir($directory) as $file) {
            if ('.' == $file || '..' == $file) {
                continue;
            }
            $iso3 = explode('.', $file)[0];

            $choices[$iso3] = realpath($directory.'/'.$file);
        }

        return $choices;
    }

    /**
     * @return Question
     */
    protected function createCountryQuestion(): Question
    {
        $directory = __DIR__.'/../Resources/locations';

        $choices = [];
        foreach (scandir($directory) as $file) {
            if ('.' == $file || '..' == $file) {
                continue;
            }
            $iso3 = explode('.', $file)[0];

            $choices[$iso3] = realpath($directory.'/'.$file);
        }

        return new ChoiceQuestion(
            'Which file do you want import? ',
            $choices
        );
    }
}
