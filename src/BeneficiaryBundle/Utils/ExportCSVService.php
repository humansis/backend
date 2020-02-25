<?php


namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ExportCSVService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    private $MAPPING_HXL = [
        "Address street" => '#contact+address_street',
        "Address number" => '#contact+address_number',
        "Address postcode" => '#contact+address_postcode',
        "Camp name" => '',
        "Tent number" => '',
        "Livelihood" => '',
        "Income level" => '',
        "Food Consumption Score" => '',
        "Coping Strategies Index" => '',
        "Notes" => '#description+notes',
        "Latitude" => '#geo+lat',
        "Longitude" => '#geo+lon',
        // Location
        "Adm1" => '#adm1+name',
        "Adm2" => '#adm2+name',
        "Adm3" => '#adm3+name',
        "Adm4" => '#adm4+name'
    ];

    private $MAPPING_CSV_EXPORT = [
        // Household
        "Address street" => 'Thompson Drive',
        "Address number" => '4943',
        "Address postcode" => '94801',
        "Camp name" => 'Some Camp',
        "Tent number" => '10',
        "Livelihood" => 'Education',
        "Income level" => '3',
        "Food Consumption Score" => '3',
        "Coping Strategies Index" => '2',
        "Notes" => 'Greatest city',
        "Latitude" => '38.018234',
        "Longitude" => '-122.379730',
        // Location
        "Adm1" => 'USA',
        "Adm2" => 'California',
        "Adm3" => 'CA',
        "Adm4" => 'Richmond'
    ];

    private $MAPPING_DEPENDENTS = [
        // Household
        "Address street" => '',
        "Address number" => '',
        "Address postcode" => '',
        "Camp name" => '',
        "Tent number" => '',
        "Livelihood" => '',
        "Income level" => '',
        "Food Consumption Score" => '',
        "Coping Strategies Index" => '',
        "Notes" => '',
        "Latitude" => '',
        "Longitude" => '',
        // Location
        "Adm1" => '',
        "Adm2" => '',
        "Adm3" => '',
        "Adm4" => ''
    ];

    private $MAPPING_DETAILS = [
        "Address street" => "String*",
        "Address number" => "Number*",
        "Address postcode" => "Number*",
        "Camp name" => 'String*',
        "Tent number" => 'Number*',
        "Livelihood" => "String",
        "Income level" => "Number [1-5]",
        "Food Consumption Score" => 'Number',
        "Coping Strategies Index" => 'Number',
        "Notes" => "String",
        "Latitude" => "Float",
        "Longitude" => "Float",
        // Location
        "Adm1" => "String/empty",
        "Adm2" => "String/empty",
        "Adm3" => "String/empty",
        "Adm4" => "String/empty",
    ];

    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
    }

    /**
     * @param string $countryISO3
     * @param string $type
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function generate(string $countryISO3, string $type)
    {
        $spreadsheet = $this->buildFile($countryISO3);
        $filename = $this->container->get('export_csv_service')->generateFile($spreadsheet, 'pattern_household_' . $countryISO3, $type);

        return $filename;
    }

    /**
     * @param $countryISO3
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function buildFile($countryISO3)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->createSheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $countrySpecifics = $this->getCountrySpecifics($countryISO3);
        $columnsCountrySpecificsAdded = false;

        $i = 0;
        $worksheet->setCellValue('A' . 1, "Household");
        foreach ($this->MAPPING_CSV_EXPORT as $CSVIndex => $name) {
            if (!$columnsCountrySpecificsAdded && $CSVIndex >= Household::firstColumnNonStatic) {
                if (!empty($countrySpecifics)) {
                    $worksheet->setCellValue(($this->SUMOfLetter($CSVIndex, $i)) . 1, "Country Specifics");
                    /** @var CountrySpecific $countrySpecific */
                    foreach ($countrySpecifics as $countrySpecific) {
                        $worksheet->setCellValue(($this->SUMOfLetter($CSVIndex, $i)) . 2, $countrySpecific->getFieldString());
                        $i++;
                    }
                }
                $worksheet->setCellValue(($this->SUMOfLetter($CSVIndex, $i)) . 1, "Beneficiary");
                $worksheet->setCellValue(($this->SUMOfLetter($CSVIndex, $i)) . 2, $name);
                $columnsCountrySpecificsAdded = true;
            } else {
                if ($CSVIndex >= Household::firstColumnNonStatic) {
                    $worksheet->setCellValue(($this->SUMOfLetter($CSVIndex, $i)) . 2, $name);
                } else {
                    $worksheet->setCellValue($CSVIndex . 2, $name);
                }
            }
        }

        return $spreadsheet;
    }

    /**
     * @param $countryISO3
     * @return mixed
     */
    private function getCountrySpecifics($countryISO3)
    {
        $countrySpecifics = $this->em->getRepository(CountrySpecific::class)->findByCountryIso3($countryISO3);
        return $countrySpecifics;
    }

    /**
     * Make an addition of a letter and a number
     * Example : A + 2 = C  Or  Z + 1 = AA  OR  AY + 2 = BA
     * @param $letter1
     * @param $number
     * @return string
     */
    private function SUMOfLetter($letter1, $number)
    {
        $ascii = ord($letter1) + $number;
        $prefix = '';
        if ($ascii > 90) {
            $prefix = 'A';
            $ascii -= 26;
            while ($ascii > 90) {
                $prefix++;
                $ascii -= 90;
            }
        }
        return $prefix . chr($ascii);
    }

    /**
     * Export all projects of the country in the CSV file
     * @param string $type
     * @param string $countryISO3
     * @return mixed
     */
    public function exportToCsv(string $type, string $countryISO3)
    {
        $tempHxl = [
            "Local given name" => '#beneficiary+localGivenName',
            "Local family name" => '#beneficiary+localFamilyName',
            "English given name" => '#beneficiary+enGivenName',
            "English family name" => '#beneficiary+enFamilyName',
            "Gender" => '',
            "Head" => '',
            "Residency status" => '',
            "Date of birth" => '#beneficiary+birth',
            "Vulnerability criteria" => '',
            "Type phone 1" => "",
            "Prefix phone 1" => "",
            "Number phone 1" => '#contact+phone',
            "Proxy phone 1" => "",
            "Type phone 2" => "",
            "Prefix phone 2" => "",
            "Number phone 2" => '#contact+phone',
            "Proxy phone 2" => "",
            "ID Type" => '',
            "ID Number" => '',
            "  " => '',
            "" => "     -->",
            " " => 'Do not remove this line.'
        ];

        $tempBenef = [
            "Local given name" => 'Price',
            "Local family name" => 'Smith',
            "English given name" => 'Price',
            "English family name" => 'Smith',
            "Gender" => 'Female',
            "Head" => 'true',
            "Residency status" => 'Refugee',
            "Date of birth" => '31-10-1990',
            "Vulnerability criteria" => 'disabled',
            "Type phone 1" => "Mobile",
            "Prefix phone 1" => "'+855",
            "Number phone 1" => "'145678348",
            "Proxy phone 1" => "N",
            "Type phone 2" => "Landline",
            "Prefix phone 2" => "'+855",
            "Number phone 2" => "'223543767",
            "Proxy phone 2" => "N",
            "ID Type" => 'IDCard',
            "ID Number" => '030617701',
            "  " => '[Head]',
            "" => "     -->",
            " " => 'This Example line and the Type Helper line below must not be removed.'
        ];

        $dependent = [
            "Local given name" => 'James',
            "Local family name" => 'Smith',
            "English given name" => 'James',
            "English family name" => 'Smith',
            "Gender" => 'Male',
            "Head" => 'false',
            "Residency status" => 'Resident',
            "Date of birth" => '25-07-2001',
            "Vulnerability criteria" => '',
            "Type phone 1" => "Mobile",
            "Prefix phone 1" => "'+855",
            "Number phone 1" => "'145678323",
            "Proxy phone 1" => "Y",
            "Type phone 2" => "Landline",
            "Prefix phone 2" => "'+855",
            "Number phone 2" => "'265348764",
            "Proxy phone 2" => "Y",
            "ID Type" => '',
            "ID Number" => '',
            "  " => '[Member]',
            "" => "     -->",
            " " => "'*' means that the property is needed -- An adm must be filled among Adm1/Adm2/Adm3/Adm4."
        ];

        $details = [
            "Local given name" => 'String*',
            "Local family name" => 'String*',
            "English given name" => 'String',
            "English family name" => 'String',
            "Gender" => 'Male / Female*',
            "Head" => 'String [true-false]*',
            "Residency status" => 'Refugee / IDP / Resident*',
            "Date of birth" => 'DD-MM-YYYY',
            "Vulnerability criteria" => 'String',
            "Type phone 1" => 'Mobile / Landline',
            "Prefix phone 1" => "'+X",
            "Number phone 1" => 'Number',
            "Proxy phone 1" => "Y / N (Proxy)",
            "Type phone 2" => 'Mobile / Landline',
            "Prefix phone 2" => "'+X",
            "Number phone 2" => 'Number',
            "Proxy phone 2" => "Y / N (Proxy)",
            "ID Type" => '"TypeAsString"',
            "ID Number" => 'Number',
            "  " => '',
            "" => "     -->",
            " " => "'*' means that the property is needed -- An adm must be filled among Adm1/Adm2/Adm3/Adm4."
        ];

        $MAPPING_CSV_EXPORT = array();

        foreach ($tempHxl as $key => $value) {
            $this->MAPPING_HXL[$key] = $value;
        }
        foreach ($tempBenef as $key => $value) {
            $this->MAPPING_CSV_EXPORT[$key] = $value;
        }
        foreach ($dependent as $key => $value) {
            $this->MAPPING_DEPENDENTS[$key] = $value;
        }
        foreach ($details as $key => $detail) {
            $this->MAPPING_DETAILS[$key] = $detail;
        }

        if ($countryISO3 === "UKR") {
            $tempHxlMember = [
                "F 0 - 2" => "",
                "F 2 - 5" => "",
                "F 6 - 17" => "",
                "F 18 - 64" => "",
                "F 65+" => "",
                "M 0 - 2" => "",
                "M 2 - 5" => "",
                "M 6 - 17" => "",
                "M 18 - 64" => "",
                "M 65+" => "",
            ];
            $tempBenefMember = [
                "F 0 - 2" => 2,
                "F 2 - 5" => 0,
                "F 6 - 17" => 0,
                "F 18 - 64" => 0,
                "F 65+" => 1,
                "M 0 - 2" => 0,
                "M 2 - 5" => 3,
                "M 6 - 17" => 0,
                "M 18 - 64" => 0,
                "M 65+" => 0,
            ];
            $dependentMember = [
                "F 0 - 2" => "",
                "F 2 - 5" => "",
                "F 6 - 17" => "",
                "F 18 - 64" => "",
                "F 65+" => "",
                "M 0 - 2" => "",
                "M 2 - 5" => "",
                "M 6 - 17" => "",
                "M 18 - 64" => "",
                "M 65+" => "",
            ];
            $detailsMember = [
                "F 0 - 2" => "Number",
                "F 2 - 5" => "Number",
                "F 6 - 17" => "Number",
                "F 18 - 64" => "Number",
                "F 65+" => "Number",
                "M 0 - 2" => "Number",
                "M 2 - 5" => "Number",
                "M 6 - 17" => "Number",
                "M 18 - 64" => "Number",
                "M 65+" => "Number",
            ];

            $this->MAPPING_HXL = $this->arrayInsert($this->MAPPING_HXL, -3, $tempHxlMember);
            $this->MAPPING_CSV_EXPORT = $this->arrayInsert($this->MAPPING_CSV_EXPORT, -3, $tempBenefMember);
            $this->MAPPING_DEPENDENTS = $this->arrayInsert($this->MAPPING_DEPENDENTS, -3, $dependentMember);
            $this->MAPPING_DETAILS = $this->arrayInsert($this->MAPPING_DETAILS, -3, $detailsMember);
        }

        $countrySpecifics = $this->getCountrySpecifics($countryISO3);
        foreach ($countrySpecifics as $countrySpecific) {
            $randomNum = rand(0, 100);
            $countryField = $countrySpecific->getFieldString();

            $this->MAPPING_HXL = $this->arrayInsert($this->MAPPING_HXL, -3, [$countryField => '']);
            $this->MAPPING_CSV_EXPORT = $this->arrayInsert($this->MAPPING_CSV_EXPORT, -3, [$countryField => $randomNum]);
            $this->MAPPING_DEPENDENTS = $this->arrayInsert($this->MAPPING_DEPENDENTS, -3, [$countryField => '']);
            $this->MAPPING_DETAILS = $this->arrayInsert($this->MAPPING_DETAILS, -3, [$countryField => $countrySpecific->getType()]);
        }

        array_push($MAPPING_CSV_EXPORT, $this->MAPPING_HXL);
        array_push($MAPPING_CSV_EXPORT, $this->MAPPING_CSV_EXPORT);
        array_push($MAPPING_CSV_EXPORT, $this->MAPPING_DEPENDENTS);
        array_push($MAPPING_CSV_EXPORT, $this->MAPPING_DETAILS);

        return $this->container->get('export_csv_service')->export($MAPPING_CSV_EXPORT, 'pattern_household_'  . $countryISO3, $type);
    }

    private function arrayInsert($array, $index, $val)
    {
        return array_slice($array, 0, $index, true) + $val + array_slice($array, $index, count($array) - 1, true) ;
    }
}
