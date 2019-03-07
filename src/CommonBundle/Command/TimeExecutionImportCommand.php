<?php


namespace CommonBundle\Command;


use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Utils\HouseholdCSVService;
use CommonBundle\Utils\Color;
use Doctrine\ORM\EntityManagerInterface;
use ProjectBundle\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class TimeExecutionImportCommand extends ContainerAwareCommand
{
    /** @var HouseholdCSVService $hhCSVService */
    private $hhCSVService;
    /** @var Color $color */
    private $color;
    /** @var EntityManagerInterface $em */
    private $em;

    private $iso3 = "KHM";
    private $addressStreet = "ADDR TEST_IMPORT";
    private $addressStreet2 = "ADDR2_TEST_IMPORT_TEST_IMPORT";
    private $addressStreet3 = "ADDR3_UNIT_TEST_UNIT";
    private $addressStreet4 = "ADDR4_UNIT4";
    private $addressStreet5 = "ADDR4_UNIT555";
    private $addressStreet6 = "ADDR4_UNIT666";
    private $addressStreet7 = "ADDR4_UNIT77777";
    private $addressStreet8 = "ADDR4_UNIT888888888";
    private $addressStreet9 = "ADDR4_UNIT999999999";
    private $addressStreet10 = "ADDR4_UNIT10000000";

    private $sumStep1 = 0;
    private $sumStep2 = 0;
    private $sumStep3 = 0;
    private $sumStep4 = 0;
    private $sumStep5 = 0;

    private $SHEET_ARRAY = [
        1 => [
            "A" => "Household",
            "B" => null,
            "C" => null,
            "D" => null,
            "E" => null,
            "F" => null,
            "G" => null,
            "H" => null,
            "I" => null,
            "J" => null,
            "K" => null,
            "L" => "Country Specifics",
            "M" => null,
            "N" => "Beneficiary",
            "O" => null,
            "P" => null,
            "Q" => null,
            "R" => null,
            "S" => null,
            "T" => null,
            "U" => null,
        ],
        2 => [
            "A" => "Address street",
            "B" => "Address number",
            "C" => "Address Postcode",
            "D" => "Livelihood",
            "E" => "Notes",
            "F" => "Latitude",
            "G" => "Longitude",
            "H" => "adm1",
            "I" => "adm2",
            "J" => "adm3",
            "K" => "adm4",
            "L" => "IDPoor",
            "M" => "equityCardNo",
            "N" => "Given name",
            "O" => "Family name",
            "P" => "Gender",
            "Q" => "Status",
            "R" => "Date of birth",
            "S" => "Vulnerability criterions",
            "T" => "Phones",
            "U" => "National Ids",
        ],
        3 => [
            "A" => "ADDR TEST_IMPORT",
            "B" => 1.0,
            "C" => 11.0,
            "D" => 10.0,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "TEST_IMPORT",
            "I" => "TEST_IMPORT",
            "J" => "TEST_IMPORT",
            "K" => "TEST_IMPORT",
            "L" => 4.0,
            "M" => "my wash",
            "N" => "FIRSTNAME TEST_IMPORT",
            "O" => "NAME TEST_IMPORT",
            "P" => "F",
            "Q" => 1,
            "R" => "1995-04-25",
            "S" => "lactating ; single family",
            "T" => "Type1 - 1",
            "U" => "card-152a",
        ],
        4 => [
            "A" => null,
            "B" => null,
            "C" => null,
            "D" => null,
            "E" => null,
            "F" => null,
            "G" => null,
            "H" => null,
            "I" => null,
            "J" => null,
            "K" => null,
            "L" => null,
            "M" => null,
            "N" => "FIRSTNAME2 TEST_IMPORT",
            "O" => "NAME2 TEST_IMPORT",
            "P" => "M",
            "Q" => 0,
            "R" => "1995-04-25",
            "S" => "lactating",
            "T" => "Type1 - 2",
            "U" => "id-45f",
        ],
        5 => [
            "A" => "ADDR2_TEST_IMPORT_TEST_IMPORT",
            "B" => 2,
            "C" => 2,
            "D" => 2,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "TEST_IMPORT212",
            "I" => "TEST_IMPORT2122",
            "J" => "TEST_IMPORT22",
            "K" => "TEST_IMPORT222",
            "L" => 4.0,
            "M" => "my wash",
            "N" => "FIRSTNAME3 UNIT_TEST",
            "O" => "FNAME33 UNIT_TEST",
            "P" => "F",
            "Q" => 1,
            "R" => "1995-04-25",
            "S" => "lactating ; single family",
            "T" => "Type1 - 1",
            "U" => "card-152a",
        ],
        6 => [
            "A" => null,
            "B" => null,
            "C" => null,
            "D" => null,
            "E" => null,
            "F" => null,
            "G" => null,
            "H" => null,
            "I" => null,
            "J" => null,
            "K" => null,
            "L" => null,
            "M" => null,
            "N" => "FIRSTNAME2 TEST_IMPORT",
            "O" => "NAME2 TEST_IMPORT",
            "P" => "M",
            "Q" => 0,
            "R" => "1995-04-25",
            "S" => "lactating",
            "T" => "Type1 - 2",
            "U" => "id-45f",
        ],
        7 => [
            "A" => "ADDR3_UNIT_TEST_UNIT",
            "B" => 3,
            "C" => 3,
            "D" => 3,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "TEST_IMPOR11T22",
            "I" => "TEST_IMPORT222",
            "J" => "TEST_IMPORT2c2",
            "K" => "TEST_IMPORT222",
            "L" => 4.0,
            "M" => "my wash",
            "N" => "FI4 UNIT_TEST",
            "O" => "FN43 UNIT_TEST",
            "P" => "F",
            "Q" => 1,
            "R" => "1995-04-25",
            "S" => "lactating ; single family",
            "T" => "Type1 - 1",
            "U" => "card-152a",
        ],
        8 => [
            "A" => null,
            "B" => null,
            "C" => null,
            "D" => null,
            "E" => null,
            "F" => null,
            "G" => null,
            "H" => null,
            "I" => null,
            "J" => null,
            "K" => null,
            "L" => null,
            "M" => null,
            "N" => "FIRSTNAME244 TEST_IMPORT",
            "O" => "NAME2 T4EST_IMPORT",
            "P" => "M",
            "Q" => 0,
            "R" => "1995-04-25",
            "S" => "lactating",
            "T" => "Type1 - 2",
            "U" => "id-45f",
        ],
        9 => [
            "A" => "ADDR4_UNIT4",
            "B" => 4,
            "C" => 4,
            "D" => 4,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "TEST_IMPORT22",
            "I" => "TEST_IMP222ORT222",
            "J" => "TEST_IMPORT2c2",
            "K" => "TEST_IMPORT222",
            "L" => 4.0,
            "M" => "my wash",
            "N" => "FIRS4M5E3 UNIT_TEST",
            "O" => "FN5A4UNIT_TEST",
            "P" => "F",
            "Q" => 1,
            "R" => "1995-04-25",
            "S" => "lactating ; single family",
            "T" => "Type1 - 1",
            "U" => "card-152a",
        ],
        10 => [
            "A" => null,
            "B" => null,
            "C" => null,
            "D" => null,
            "E" => null,
            "F" => null,
            "G" => null,
            "H" => null,
            "I" => null,
            "J" => null,
            "K" => null,
            "L" => null,
            "M" => null,
            "N" => "FIRSTNA55M56E2 TEST_IMPORT",
            "O" => "NAME2 T5556EST_IMPORT",
            "P" => "M",
            "Q" => 0,
            "R" => "1995-04-25",
            "S" => "lactating",
            "T" => "Type1 - 2",
            "U" => "id-45f",
        ],
        11 => [
            "A" => "ADDR4_UNIT555",
            "B" => 4,
            "C" => 4,
            "D" => 4,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "TEST_IMPORT22",
            "I" => "TEST_IMPORTd222",
            "J" => "TEST_IMPO33333RT22",
            "K" => "TEST_IMPORT222",
            "L" => 4.0,
            "M" => "my wash",
            "N" => "FIRS4M5E3 U55NIT_TEST",
            "O" => "FN5A4UNIT_TES5555T",
            "P" => "F",
            "Q" => 1,
            "R" => "1995-04-25",
            "S" => "lactating ; single family",
            "T" => "Type1 - 1",
            "U" => "card-152a",
        ],
        12 => [
            "A" => null,
            "B" => null,
            "C" => null,
            "D" => null,
            "E" => null,
            "F" => null,
            "G" => null,
            "H" => null,
            "I" => null,
            "J" => null,
            "K" => null,
            "L" => null,
            "M" => null,
            "N" => "FIRSTNA55M56E662 TEST_IMPORT",
            "O" => "NAME2 T5556EST_IM666PORT",
            "P" => "M",
            "Q" => 0,
            "R" => "1995-04-25",
            "S" => "lactating",
            "T" => "Type1 - 2",
            "U" => "id-45f",
        ],
        13 => [
            "A" => "ADDR4_UNIT666",
            "B" => 4,
            "C" => 4,
            "D" => 4,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "TEST_IMPORT2e2",
            "I" => "TEST_IMPORT222",
            "J" => "TEST_IMPORT22",
            "K" => "TEST_IMPORT4444222",
            "L" => 4.0,
            "M" => "my wash",
            "N" => "FIRS4M5E377777 UNIT_TEST",
            "O" => "FN5A4UNIT_T777777EST",
            "P" => "F",
            "Q" => 1,
            "R" => "1995-04-25",
            "S" => "lactating ; single family",
            "T" => "Type1 - 1",
            "U" => "card-152a",
        ],
        14 => [
            "A" => null,
            "B" => null,
            "C" => null,
            "D" => null,
            "E" => null,
            "F" => null,
            "G" => null,
            "H" => null,
            "I" => null,
            "J" => null,
            "K" => null,
            "L" => null,
            "M" => null,
            "N" => "FIRSTNA55M56E8882 TEST_IMPORT",
            "O" => "NAME2 T5556E888888ST_IMPORT",
            "P" => "M",
            "Q" => 0,
            "R" => "1995-04-25",
            "S" => "lactating",
            "T" => "Type1 - 2",
            "U" => "id-45f",
        ],
        15 => [
            "A" => "ADDR4_UNIT77777",
            "B" => 4,
            "C" => 4,
            "D" => 4,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "TEST_IMPOR5555T22",
            "I" => "TEST_IMPORT2r22",
            "J" => "TEST_IMPORT22",
            "K" => "TEST_IMPORT222",
            "L" => 4.0,
            "M" => "my wash",
            "N" => "FIRS4M5E3 UN999999IT_TEST",
            "O" => "FN5A4UNI999T_TEST",
            "P" => "F",
            "Q" => 1,
            "R" => "1995-04-25",
            "S" => "lactating ; single family",
            "T" => "Type1 - 1",
            "U" => "card-152a",
        ],
        16 => [
            "A" => null,
            "B" => null,
            "C" => null,
            "D" => null,
            "E" => null,
            "F" => null,
            "G" => null,
            "H" => null,
            "I" => null,
            "J" => null,
            "K" => null,
            "L" => null,
            "M" => null,
            "N" => "FIRSTNA55M561000000E2 TEST_IMPORT",
            "O" => "NAME2 T5556E11111000000000000ST_IMPORT",
            "P" => "M",
            "Q" => 0,
            "R" => "1995-04-25",
            "S" => "lactating",
            "T" => "Type1 - 2",
            "U" => "id-45f",
        ],
        17 => [
            "A" => "ADDR4_UNIT888888888",
            "B" => 4,
            "C" => 4,
            "D" => 4,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "TEST_IMPORT22",
            "I" => "TEST_IMPORTz222",
            "J" => "TEST_IMPOR6666T22",
            "K" => "TEST_IMPORT222",
            "L" => 4.0,
            "M" => "my wash",
            "N" => "FIRS4M5E3 U11111111111NIT_TEST",
            "O" => "FN5A4UNIT_T1111111111111111111EST",
            "P" => "F",
            "Q" => 1,
            "R" => "1995-04-25",
            "S" => "lactating ; single family",
            "T" => "Type1 - 1",
            "U" => "card-152a",
        ],
        18 => [
            "A" => null,
            "B" => null,
            "C" => null,
            "D" => null,
            "E" => null,
            "F" => null,
            "G" => null,
            "H" => null,
            "I" => null,
            "J" => null,
            "K" => null,
            "L" => null,
            "M" => null,
            "N" => "FIRSTNA55M56E2 121212121TEST_IMPORT",
            "O" => "NAME2 T5556EST12121212_IMPORT",
            "P" => "M",
            "Q" => 0,
            "R" => "1995-04-25",
            "S" => "lactating",
            "T" => "Type1 - 2",
            "U" => "id-45f",
        ],
        19 => [
            "A" => "ADDR4_UNIT999999999",
            "B" => 4,
            "C" => 4,
            "D" => 4,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "TEST_IMPOR77T22",
            "I" => "TEST_IMPORT222",
            "J" => "TEST_IMPOR777oT22",
            "K" => "TEST_IMPORT222",
            "L" => 4.0,
            "M" => "my wash",
            "N" => "FIRS4M5E3 UNI13131313131T_TEST",
            "O" => "FN5A4UNIT_131313131313TEST",
            "P" => "F",
            "Q" => 1,
            "R" => "1995-04-25",
            "S" => "lactating ; single family",
            "T" => "Type1 - 1",
            "U" => "card-152a",
        ],
        20 => [
            "A" => null,
            "B" => null,
            "C" => null,
            "D" => null,
            "E" => null,
            "F" => null,
            "G" => null,
            "H" => null,
            "I" => null,
            "J" => null,
            "K" => null,
            "L" => null,
            "M" => null,
            "N" => "FIRSTNA55M561331313131313E2 TEST_IMPORT",
            "O" => "NAME2 T5556ES1313131313T_IMPORT",
            "P" => "M",
            "Q" => 0,
            "R" => "1995-04-25",
            "S" => "lactating",
            "T" => "Type1 - 2",
            "U" => "id-45f",
        ],
        21 => [
            "A" => "ADDR4_UNIT10000000",
            "B" => 4,
            "C" => 4,
            "D" => 4,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "TEST_IMPORT22",
            "I" => "TEST_IMPORpT222",
            "J" => "TEST_IMPOR8888T22",
            "K" => "TEST_IMPORT222",
            "L" => 4.0,
            "M" => "my wash",
            "N" => "FIRS4M5E3 U131344413131NIT_TEST",
            "O" => "FN5A4UNIT_T3131344441313EST",
            "P" => "F",
            "Q" => 1,
            "R" => "1995-04-25",
            "S" => "lactating ; single family",
            "T" => "Type1 - 1",
            "U" => "card-152a",
        ],
        22 => [
            "A" => null,
            "B" => null,
            "C" => null,
            "D" => null,
            "E" => null,
            "F" => null,
            "G" => null,
            "H" => null,
            "I" => null,
            "J" => null,
            "K" => null,
            "L" => null,
            "M" => null,
            "N" => "FIRSTNA55M56E21141414141414 TEST_IMPORT",
            "O" => "NAME2 T5556ES4141414141414T_IMPORT",
            "P" => "M",
            "Q" => 0,
            "R" => "1995-04-25",
            "S" => "lactating",
            "T" => "Type1 - 2",
            "U" => "id-45f",
        ]
    ];

    protected function configure()
    {
        $this
            ->setName('ra:import:test')
            ->setDescription('Display execution time of import csv')
            ->setHelp('Display the execution time for the 5 steps of the csv import of households');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->color = new Color();
        $this->hhCSVService = $this->getContainer()->get('beneficiary.household_csv_service');
        $output->writeln([
            '',
            '============================================================',
            "Execution time of csv import",
            '============================================================',
            '',
        ]);

        $this->removeAll();

        $projects = $this->em->getRepository(Project::class)->findAll();
        if (empty($projects))
        {
            print_r("\nThere is no project in your database.\n\n");
            return;
        }

        $helper = $this->getHelper('question');
        $questionNumber = new Question('How many import : ');
        $number = $helper->ask($input, $output, $questionNumber);

        for ($i = 1; $i <= intval($number); $i++)
        {
            $this->calcExecTime($input, $projects, $i);
        }

        unset($helper);
        unset($questionNumber);


        print_r($this->color->getColoredString("\n\n-----------------"));
        print_r($this->color->getColoredString("\nRESUME", "light_red"));
        print_r($this->color->getColoredString("\nStep 1 - Average : ", "yellow"));
        print_r($this->color->getColoredString(number_format(($this->sumStep1 / $number), 3)));
        print_r($this->color->getColoredString("\nStep 2 - Average : ", "yellow"));
        print_r($this->color->getColoredString(number_format(($this->sumStep2 / $number), 3)));
        print_r($this->color->getColoredString("\nStep 3 - Average : ", "yellow"));
        print_r($this->color->getColoredString(number_format(($this->sumStep3 / $number), 3)));
        print_r($this->color->getColoredString("\nStep 4 - Average : ", "yellow"));
        print_r($this->color->getColoredString(number_format(($this->sumStep4 / $number), 3)));
        print_r($this->color->getColoredString("\nStep 5 - Average : ", "yellow"));
        print_r($this->color->getColoredString(number_format(($this->sumStep5 / $number), 3)) . "\n\n");

        unset($number);

        $this->removeAll();

        $output->writeln([
            'END'
        ]);
    }

    /**
     * @param InputInterface $input
     * @param $projects
     * @param $step
     * @throws \Exception
     */
    public function calcExecTime(InputInterface $input, $projects, $step)
    {
        $totalTime = 0;
        print_r($this->color->getColoredString("\n\n$step-"));
        $executionStartTime = microtime(true);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), $this->SHEET_ARRAY, 1, null);
        $executionTime = microtime(true);
        if ($input->hasOption('verbose'))
        {
            print_r($this->color->getColoredString("\nstep 1 - Execution time : ", "yellow"));
            print_r($this->color->getColoredString(number_format($executionTime - $executionStartTime, 3) . " s"));
        }
        $totalTime += ($executionTime - $executionStartTime);
        if (1 !== $step)
            $this->sumStep1 += ($executionTime - $executionStartTime);
        $token = $return["token"];
        $executionStartTime = microtime(true);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 2, $token);
        $executionTime = microtime(true);
        if ($input->hasOption('verbose'))
        {
            print_r($this->color->getColoredString("\nstep 2 - Execution time : ", "yellow"));
            print_r($this->color->getColoredString(number_format($executionTime - $executionStartTime, 3) . " s"));
        }
        $totalTime += ($executionTime - $executionStartTime);
        if (1 !== $step)
            $this->sumStep2 += ($executionTime - $executionStartTime);
        $executionStartTime = microtime(true);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 3, $token);
        $executionTime = microtime(true);
        if ($input->hasOption('verbose'))
        {
            print_r($this->color->getColoredString("\nstep 3 - Execution time : ", "yellow"));
            print_r($this->color->getColoredString(number_format($executionTime - $executionStartTime, 3) . " s"));
        }
        $totalTime += ($executionTime - $executionStartTime);
        if (1 !== $step)
            $this->sumStep3 += ($executionTime - $executionStartTime);
        $executionStartTime = microtime(true);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 4, $token);
        $executionTime = microtime(true);
        if ($input->hasOption('verbose'))
        {
            print_r($this->color->getColoredString("\nstep 4 - Execution time : ", "yellow"));
            print_r($this->color->getColoredString(number_format($executionTime - $executionStartTime, 3) . " s"));
        }
        $totalTime += ($executionTime - $executionStartTime);
        if (1 !== $step)
            $this->sumStep4 += ($executionTime - $executionStartTime);
        $executionStartTime = microtime(true);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 5, $token);
        $executionTime = microtime(true);
        if ($input->hasOption('verbose'))
        {
            print_r($this->color->getColoredString("\nstep 5 - Execution time : ", "yellow"));
            print_r($this->color->getColoredString(number_format($executionTime - $executionStartTime, 3) . " s"));
        }
        $totalTime += ($executionTime - $executionStartTime);
        if (1 !== $step)
            $this->sumStep5 += ($executionTime - $executionStartTime);
        print_r($this->color->getColoredString("\nExecution time : ", "light_red"));
        print_r($this->color->getColoredString(number_format($totalTime, 3) . " s\n"));
        print_r($this->color->getColoredString("\n---------------------------------------"));
        unset($totalTime);
        unset($executionTime);
        unset($executionStartTime);
    }

    /**
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function removeAll()
    {
        $this->remove($this->addressStreet);
        $this->remove($this->addressStreet2);
        $this->remove($this->addressStreet3);
        $this->remove($this->addressStreet4);
        $this->remove($this->addressStreet5);
        $this->remove($this->addressStreet6);
        $this->remove($this->addressStreet7);
        $this->remove($this->addressStreet8);
        $this->remove($this->addressStreet9);
        $this->remove($this->addressStreet10);
    }

    /**
     * @depends testGetHouseholds
     *
     * @param $addressStreet
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove($addressStreet)
    {
        $this->em->clear();
        /** @var Household $household */
        $household = $this->em->getRepository(Household::class)->findOneByAddressStreet($addressStreet);
        if ($household instanceof Household)
        {
            $beneficiaries = $this->em->getRepository(Beneficiary::class)->findByHousehold($household);
            if (!empty($beneficiaries))
            {
                /** @var Beneficiary $beneficiary */
                foreach ($beneficiaries as $beneficiary)
                {
                    $phones = $this->em->getRepository(Phone::class)->findByBeneficiary($beneficiary);
                    $nationalIds = $this->em->getRepository(NationalId::class)->findByBeneficiary($beneficiary);
                    foreach ($phones as $phone)
                    {
                        $this->em->remove($phone);
                    }
                    foreach ($nationalIds as $nationalId)
                    {
                        $this->em->remove($nationalId);
                    }
                    $this->em->remove($beneficiary->getProfile());
                    $this->em->remove($beneficiary);
                }
            }

            $countrySpecificAnswers = $this->em->getRepository(CountrySpecificAnswer::class)
                ->findByHousehold($household);
            foreach ($countrySpecificAnswers as $countrySpecificAnswer)
            {
                $this->em->remove($countrySpecificAnswer);
            }

            $this->em->remove($household);
            $location = $household->getLocation();
            $this->em->remove($location);
            try
            {
                $this->em->flush();
            }
            catch (\Exception $exception)
            {

            }
        }
    }
}