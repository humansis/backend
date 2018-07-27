<?php


namespace BeneficiaryBundle\Utils;


use BeneficiaryBundle\Entity\CountrySpecific;
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

    private $MAPPING_CSV = [
        // Household
        "A" => "Address street",
        "B" => "Address number",
        "C" => "Address postcode",
        "D" => "Livelihood",
        "E" => "Notes",
        "F" => "Latitude",
        "G" => "Longitude",
        // Location
        "H" => "Adm1",
        "I" => "Adm2",
        "J" => "Adm3",
        "K" => "Adm4",
        // Beneficiary
        "L" => "Given name",
        "M" => "Family name",
        "N" => "Gender",
        "O" => "Status",
        "P" => "Date of birth",
        "Q" => "Vulnerability criteria",
        "R" => "Phones",
        "S" => "National IDs"
    ];

    /**
     * First value with a column in the csv which can move, depends on the number of country specifics
     * @var string
     */
    private $firstColumnNonStatic = 'L';

    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
    }

    /**
     * @param $countryISO3
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function generate($countryISO3)
    {
        $spreadsheet = $this->buildFile($countryISO3);
        $writer = new Csv($spreadsheet);
        $dataPath = $this->container->getParameter('kernel.root_dir') . '/../var';
        $filename = $dataPath . '/pattern_household_' . $countryISO3 . '.csv';
        $writer->save($filename);

        //Récupération du contenu et suppression du fichier
        $fileContent = file_get_contents($filename);
        unlink($filename);
        $file = [$fileContent, 'pattern_household_' . $countryISO3 . '.csv'];
        return $file;
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
        foreach ($this->MAPPING_CSV as $CSVIndex => $name)
        {
            if (!$columnsCountrySpecificsAdded && $CSVIndex >= $this->firstColumnNonStatic)
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
                if ($CSVIndex >= $this->firstColumnNonStatic)
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
}