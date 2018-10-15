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

    private $MAPPING_CSV_EXPORT = [
        // Household
        "Address street" => 'Thompson Drive',
        "Address number" => '4943',
        "Address postcode" => '94801',
        "Livelihood" => 'Richmond',
        "Notes" => 'Greatest city',
        "Latitude" => '38.018234',
        "Longitude" => '-122.379730',
        // Location
        "Adm1" => 'USA',
        "Adm2" => 'California',
        "Adm3" => 'CA',
        "Adm4" => 'Richmond'
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
        foreach ($this->MAPPING_CSV_EXPORT as $CSVIndex => $name)
        {
            if (!$columnsCountrySpecificsAdded && $CSVIndex >= Household::firstColumnNonStatic)
            {
                if (!empty($countrySpecifics))
                {
                    $worksheet->setCellValue(($this->SUMOfLetter($CSVIndex, $i)) . 1, "Country Specifics");
                    /** @var CountrySpecific $countrySpecific */
                    foreach ($countrySpecifics as $countrySpecific)
                    {
                        $worksheet->setCellValue(($this->SUMOfLetter($CSVIndex, $i)) . 2, $countrySpecific->getFieldString());
                        $i++;
                    }
                }
                $worksheet->setCellValue(($this->SUMOfLetter($CSVIndex, $i)) . 1, "Beneficiary");
                $worksheet->setCellValue(($this->SUMOfLetter($CSVIndex, $i)) . 2, $name);
                $columnsCountrySpecificsAdded = true;
            }
            else
            {
                if ($CSVIndex >= Household::firstColumnNonStatic)
                {
                    $worksheet->setCellValue(($this->SUMOfLetter($CSVIndex, $i)) . 2, $name);
                }
                else
                {
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
        if ($ascii > 90)
        {
            $prefix = 'A';
            $ascii -= 26;
            while ($ascii > 90)
            {
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
    public function exportToCsv(string $type, string $countryISO3) {
        $tempBenef = [
            "Given name" => 'Price',
            "Family name" => 'Smith',
            "Gender" => '0',
            "Status" => '1',
            "Date of birth" => '1997-10-31',
            "Vulnerability criteria" => 'disabled',
            "Phones" => '',
            "National IDs" => '',
            "" => 'This is an example. Please, do not remove this line'
        ];

        $MAPPING_CSV_EXPORT = array();
        $countrySpecifics = $this->getCountrySpecifics($countryISO3);
        foreach ($countrySpecifics as $countrySpecific){
            $randomNum = rand(0, 100);
            $this->MAPPING_CSV_EXPORT[$countrySpecific->getFieldString()] = $randomNum;
        }
        foreach ($tempBenef as $key => $value)
            $this->MAPPING_CSV_EXPORT[$key] = $value;

        array_push($MAPPING_CSV_EXPORT, $this->MAPPING_CSV_EXPORT);

        return $this->container->get('export_csv_service')->export($MAPPING_CSV_EXPORT, 'pattern_household_'  . $countryISO3, $type);
    }
}