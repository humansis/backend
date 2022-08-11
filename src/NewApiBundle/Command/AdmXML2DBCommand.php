<?php

namespace NewApiBundle\Command;

use NewApiBundle\Repository\LocationRepository;
use CommonBundle\Utils\LocationImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class AdmXML2DBCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LocationRepository
     */
    private $locationRepository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LocationRepository     $locationRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        LocationRepository     $locationRepository
    ) {
        $this->entityManager = $entityManager;
        $this->locationRepository = $locationRepository;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:adm:upload')
            ->setDescription('Interactive import ADM into DB')
            ->addArgument('country', InputArgument::IS_ARRAY, 'Country iso3 code')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Use all known locations')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Adm count limit per country')
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
        if ($input->hasArgument('country') && !empty($input->getArgument('country'))) {
            $countries = $input->getArgument('country');
        } elseif (empty($input->getArgument('country'))
            && true === $input->getOption('all')) {
            $countries = array_keys($this->getADMFiles());
        } else {
            $countries = [$this->getHelper('question')->ask($input, $output, new ChoiceQuestion(
                'Which file do you want import? ',
                $this->getADMFiles()
            ))];
        }
        $output->writeln("Countries to upload: ".implode(', ', $countries));
        foreach ($countries as $countryCode) {
            if (!isset($this->getADMFiles()[$countryCode])) {
                $output->writeln("$countryCode is not valid iso3 country code");
                continue;
            }
            $countryFile = $this->getADMFiles()[$countryCode];
            $output->writeln("Importing file $countryFile");

            // LOCATION IMPORT
            $importer = new LocationImporter($this->entityManager, $countryFile, $this->locationRepository);
            $this->importLocations($input, $output, $importer);
        }

        return 0;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param AdmsImporter|LocationImporter $importer
     */
    private function importLocations(InputInterface $input, OutputInterface $output, $importer): void
    {
        $output->writeln(" - Importing by ".get_class($importer));
        if ($input->hasOption('limit')) {
            $importer->setLimit($input->getOption('limit'));
        }

        $progressBar = new ProgressBar($output, $importer->getCount());
        $progressBar->start();

        foreach ($importer->importLocations() as $importStatus) {
            $progressBar->advance();

            if (isset($importStatus['inconsistent'])) {
                $oldName = $importStatus['old'];
                $newName = $importStatus['new'];
                $output->writeln("Duplicity code but name inconsistency, old=$oldName, new=$newName");
            }
        }

        $progressBar->finish();
        $output->writeln([
            "",
            "DONE, imported {$importer->getImportedLocations()}, omitted {$importer->getOmittedLocations()}",
        ]);
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
