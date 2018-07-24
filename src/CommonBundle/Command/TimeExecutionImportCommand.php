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
    private $addressStreet2 = "ADDR2 TEST_IMPORT_TEST_IMPORT";
    private $addressStreet3 = "ADDR3 UNIT TEST UNIT";
    private $addressStreet4 = "ADDR4 UNIT4";
    private $addressStreet5 = "ADDR4 UNIT555";
    private $addressStreet6 = "ADDR4 UNIT666";
    private $addressStreet7 = "ADDR4 UNIT77777";
    private $addressStreet8 = "ADDR4 UNIT888888888";
    private $addressStreet9 = "ADDR4 UNIT999999999";
    private $addressStreet10 = "ADDR4 UNIT10000000";


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
            "L" => "ID Poor",
            "M" => "WASH",
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
        ]
    ];

    protected function configure()
    {
        $this
            ->setName('ra:import:test')
            ->setDescription('Display execution time of import csv')
            ->setHelp('Display the execution time for the 5 steps of the csv import of households');
        $this->em = $this->getContainer()->get('doctrine')->getManager();
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
            "Execution time of csv import",
            '============================================================',
            '',
        ]);


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
        $projects = $this->em->getRepository(Project::class)->findAll();
        if (empty($projects))
        {
            print_r("\nThere is no project in your database.\n\n");
            return;
        }
        $this->SHEET_ARRAY[5] = [
            "A" => $this->addressStreet2,
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
        ];
        $this->SHEET_ARRAY[6] = [
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
        ];
        $this->SHEET_ARRAY[7] = [
            "A" => $this->addressStreet3,
            "B" => 3,
            "C" => 3,
            "D" => 3,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "TEST_IMPOR11T22",
            "I" => "TEST_IMPORT222",
            "J" => "TEST_IMPORT22",
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
        ];
        $this->SHEET_ARRAY[8] = [
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
        ];
        $this->SHEET_ARRAY[9] = [
            "A" => $this->addressStreet4,
            "B" => 4,
            "C" => 4,
            "D" => 4,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "TEST_IMPORT22",
            "I" => "TEST_IMP222ORT222",
            "J" => "TEST_IMPORT22",
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
        ];
        $this->SHEET_ARRAY[10] = [
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
        ];
        $this->SHEET_ARRAY[11] = [
            "A" => $this->addressStreet5,
            "B" => 4,
            "C" => 4,
            "D" => 4,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "TEST_IMPORT22",
            "I" => "TEST_IMPORT222",
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
        ];
        $this->SHEET_ARRAY[12] = [
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
        ];
        $this->SHEET_ARRAY[13] = [
            "A" => $this->addressStreet6,
            "B" => 4,
            "C" => 4,
            "D" => 4,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "TEST_IMPORT22",
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
        ];
        $this->SHEET_ARRAY[14] = [
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
        ];
        $this->SHEET_ARRAY[15] = [
            "A" => $this->addressStreet7,
            "B" => 4,
            "C" => 4,
            "D" => 4,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "TEST_IMPOR5555T22",
            "I" => "TEST_IMPORT222",
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
        ];
        $this->SHEET_ARRAY[16] = [
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
        ];
        $this->SHEET_ARRAY[17] = [
            "A" => $this->addressStreet8,
            "B" => 4,
            "C" => 4,
            "D" => 4,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "TEST_IMPORT22",
            "I" => "TEST_IMPORT222",
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
        ];
        $this->SHEET_ARRAY[18] = [
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
        ];
        $this->SHEET_ARRAY[19] = [
            "A" => $this->addressStreet9,
            "B" => 4,
            "C" => 4,
            "D" => 4,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "TEST_IMPOR77T22",
            "I" => "TEST_IMPORT222",
            "J" => "TEST_IMPOR777T22",
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
        ];
        $this->SHEET_ARRAY[20] = [
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
        ];
        $this->SHEET_ARRAY[21] = [
            "A" => $this->addressStreet10,
            "B" => 4,
            "C" => 4,
            "D" => 4,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "TEST_IMPORT22",
            "I" => "TEST_IMPORT222",
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
        ];
        $this->SHEET_ARRAY[22] = [
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
        ];

        print_r($this->color->getColoredString("\n\n1-\n"));
        $executionStartTime = microtime(true);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), $this->SHEET_ARRAY, 1, null);
        $execution1Time = microtime(true);
        if(in_array('--verbose', $_SERVER['argv'], true) || in_array('-v', $_SERVER['argv'], true)) {
            print_r($this->color->getColoredString("\n\nstep 1 : Execution time :\n", "light_red"));
            print_r($this->color->getColoredString(($execution1Time - $executionStartTime) . "\n"));
        }
        $token = $return["token"];
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 2, $token);
        $execution2Time = microtime(true);
        if(in_array('--verbose', $_SERVER['argv'], true) || in_array('-v', $_SERVER['argv'], true)) {
            print_r($this->color->getColoredString("\n\nstep 2 : Execution time :\n", "light_red"));
            print_r($this->color->getColoredString(($execution2Time - $executionStartTime) . "\n"));
        }
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 3, $token);
        $execution3Time = microtime(true);
        if(in_array('--verbose', $_SERVER['argv'], true) || in_array('-v', $_SERVER['argv'], true)) {
            print_r($this->color->getColoredString("\n\nstep 3 : Execution time :\n", "light_red"));
            print_r($this->color->getColoredString(($execution3Time - $executionStartTime) . "\n"));
        }
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 4, $token);
        $execution4Time = microtime(true);
        if(in_array('--verbose', $_SERVER['argv'], true) || in_array('-v', $_SERVER['argv'], true)) {
            print_r($this->color->getColoredString("\n\nstep 4 : Execution time :\n", "light_red"));
            print_r($this->color->getColoredString(($execution4Time - $executionStartTime) . "\n"));
        }
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 5, $token);
        $execution5Time = microtime(true);
        if(in_array('--verbose', $_SERVER['argv'], true) || in_array('-v', $_SERVER['argv'], true)) {
            print_r($this->color->getColoredString("\n\nstep 5 : Execution time :\n", "light_red"));
            print_r($this->color->getColoredString(($execution5Time - $executionStartTime) . "\n"));
        }
        $executionEndTime = microtime(true);

        print_r($this->color->getColoredString("\n\nExecution time :\n", "light_red"));
        print_r($this->color->getColoredString(($executionEndTime - $executionStartTime) . "\n"));


        $executionStartTime = microtime(true);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), $this->SHEET_ARRAY, 1, null);
        $token = $return["token"];
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 2, $token);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 3, $token);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 4, $token);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 5, $token);
        $executionEndTime = microtime(true);

        print_r($this->color->getColoredString("\n\n2- Execution time :\n", "light_red"));
        print_r($this->color->getColoredString(($executionEndTime - $executionStartTime) . "\n"));

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


        $output->writeln([
            'END'
        ]);
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
            $location = $household->getLocation();
            $this->em->remove($location);

            $countrySpecificAnswers = $this->em->getRepository(CountrySpecificAnswer::class)
                ->findByHousehold($household);
            foreach ($countrySpecificAnswers as $countrySpecificAnswer)
            {
                $this->em->remove($countrySpecificAnswer);
            }

            $this->em->remove($household);
            $this->em->flush();
        }
    }
}