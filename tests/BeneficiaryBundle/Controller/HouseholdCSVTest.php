<?php


namespace Tests\BeneficiaryBundle\Controller;


use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Model\ImportStatistic;
use BeneficiaryBundle\Utils\ExportCSVService;
use BeneficiaryBundle\Utils\HouseholdCSVService;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\BMSServiceTestCase;

class HouseholdCSVTest extends BMSServiceTestCase
{
    /** @var HouseholdCSVService $hhCSVService */
    private $hhCSVService;
    /** @var ExportCSVService $exportCSVService */
    private $exportCSVService;

    private $iso3 = "KHM";
    private $addressStreet = "ADDR TEST_IMPORT";

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
            "Q" => 0.0,
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
            "Q" => 1.0,
            "R" => "1995-04-25",
            "S" => "lactating",
            "T" => "Type1 - 2",
            "U" => "id-45f",
        ]
    ];

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        parent::setUpFunctionnal();
        $this->hhCSVService = $this->container->get('beneficiary.household_csv_service');
        $this->exportCSVService = $this->container->get('beneficiary.household_export_csv_service');
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function testExportCSV()
    {
        $countrySpecifics = $this->em->getRepository(CountrySpecific::class)->findByCountryIso3($this->iso3);
        $csvGenerated = $this->exportCSVService->generateCSV($this->iso3);
        $csvArray = str_replace('"', '', explode(",", explode("\n", current($csvGenerated))[1]));
        $this->assertContains("Address street", $csvArray);
        $this->assertContains("Address number", $csvArray);
        $this->assertContains("Address postcode", $csvArray);
        $this->assertContains("Livelihood", $csvArray);
        $this->assertContains("Notes", $csvArray);
        $this->assertContains("Latitude", $csvArray);
        $this->assertContains("Longitude", $csvArray);
        $this->assertContains("Adm1", $csvArray);
        $this->assertContains("Adm2", $csvArray);
        $this->assertContains("Adm3", $csvArray);
        $this->assertContains("Adm4", $csvArray);
        $this->assertContains("Given name", $csvArray);
        $this->assertContains("Family name", $csvArray);
        $this->assertContains("Gender", $csvArray);
        $this->assertContains("Status", $csvArray);
        $this->assertContains("Date of birth", $csvArray);
        $this->assertContains("Vulnerability criteria", $csvArray);
        $this->assertContains("Phones", $csvArray);
        $this->assertContains("National IDs", $csvArray);

        foreach ($countrySpecifics as $countrySpecific)
        {
            $this->assertContains($countrySpecific->getField(), $csvArray);
        }
    }

    /**
     * @throws \Exception
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function testImportCSV()
    {
        $projects = $this->em->getRepository(Project::class)->findAll();
        if (empty($projects))
        {
            print_r("\nThere is no project in your database.\n\n");
            return;
        }
        /** @var ImportStatistic $statistic */
        [$statistic, $listSimilarHouseholds] = $this->hhCSVService->loadCSV($this->iso3, current($projects), $this->SHEET_ARRAY);
        try
        {
            $this->assertSame([], $listSimilarHouseholds);
            $this->assertSame(1, $statistic->getNbAdded());
            $this->assertSame(0, $statistic->getNbDuplicates());
            $this->assertSame(0, $statistic->getNbIncomplete());
            [$statistic2, $listSimilarHouseholds2] = $this->hhCSVService->loadCSV($this->iso3, current($projects), $this->SHEET_ARRAY);
            $this->assertSame(0, $statistic2->getNbAdded());
            $this->assertSame(1, $statistic2->getNbDuplicates());
            $this->assertSame(0, $statistic2->getNbIncomplete());
            $this->assertArrayHasKey("new", current($listSimilarHouseholds2));
            $this->assertArrayHasKey("old", current($listSimilarHouseholds2));
            $this->assertInstanceOf(Household::class, current(current($listSimilarHouseholds2)["old"]));
            $this->SHEET_ARRAY[4]["N"] = null;
            [$statistic3, $listSimilarHouseholds3] = $this->hhCSVService->loadCSV($this->iso3, current($projects), $this->SHEET_ARRAY);
            $this->assertSame(0, $statistic3->getNbAdded());
            $this->assertSame(0, $statistic3->getNbDuplicates());
            $this->assertSame(1, $statistic3->getNbIncomplete());
            $this->assertSame([], $listSimilarHouseholds3);
        }
        catch (\Exception $exception)
        {
            // "0;31"
//            print_r("\n\n\033[1;31mERROR\033[0m\n");
            $this->remove($this->addressStreet);
            $this->fail($exception->getMessage() . "\n\n");
        }
        $this->remove($this->addressStreet);
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