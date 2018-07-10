<?php


namespace Tests\BeneficiaryBundle\Controller;


use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Utils\ExportCSVService;
use BeneficiaryBundle\Utils\HouseholdCSVService;
use Tests\BMSServiceTestCase;

class HouseholdCSVTest extends BMSServiceTestCase
{
    /** @var HouseholdCSVService $hhCSVService */
    private $hhCSVService;
    /** @var ExportCSVService $exportCSVService */
    private $exportCSVService;

    private $iso3 = "KHM";

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

}