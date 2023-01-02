<?php

namespace Command;

use Exception;
use Repository\LocationRepository;
use Services\LocationService;
use Utils\LocationImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class UpdateAdmLocations extends Command
{

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LocationRepository $locationRepository,
        private readonly LocationService $locationService
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:adm:update')
            ->setDescription('Interactive update ADM into DB')
            ->addArgument('country', InputArgument::IS_ARRAY, 'Country iso3 code')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Use all known locations')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Adm count limit per country');
    }

    /**
     *
     * @return int|void|null
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $admFiles = $this->locationService->getADMFiles();
        $countries = $this->getRequestedCountries($input, $output, $admFiles);
        $output->writeln("Countries to upload: " . implode(', ', $countries));
        foreach ($countries as $countryCode) {
            if (!isset($admFiles[$countryCode])) {
                $output->writeln("$countryCode is not valid iso3 country code");
                continue;
            }
            $countryFileUrl = $admFiles[$countryCode];
            $output->writeln("Importing file $countryFileUrl");

            // LOCATION IMPORT
            $importer = new LocationImporter($this->entityManager, $countryFileUrl, $this->locationRepository);
            $this->importLocations($input, $output, $importer);
        }

        return 0;
    }

    private function getRequestedCountries(InputInterface $input, OutputInterface $output, array $admFiles)
    {
        if ($input->hasArgument('country') && !empty($input->getArgument('country'))) {
            $countries = $input->getArgument('country');
        } elseif (
            empty($input->getArgument('country'))
            && true === $input->getOption('all')
        ) {
            $countries = array_keys($admFiles);
        } else {
            $countries = [
                $this->getHelper('question')->ask(
                    $input,
                    $output,
                    new ChoiceQuestion(
                        'Which file do you want import? ',
                        $admFiles
                    )
                ),
            ];
        }
        return $countries;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param LocationImporter $importer
     * @throws \Doctrine\DBAL\Exception
     */
    private function importLocations(InputInterface $input, OutputInterface $output, LocationImporter $importer): void
    {
        $output->writeln(" - Importing by " . $importer::class);
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

}
