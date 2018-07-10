<?php


namespace BeneficiaryBundle\Utils;


use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class HouseholdCSVService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var HouseholdService $householdService */
    private $householdService;

    private $percentSimilar = 90;

    /**
     * The row index of the header (with the name of country specifics)
     * @var int
     */
    private $indexRowHeader = 2;

    /**
     * First row with data
     * @var int $first_row
     */
    private $first_row = 3;

    /**
     * First value with a column in the csv which can move, depends on the number of country specifics
     * @var string
     */
    private $firstColumnNonStatic = 'L';

    /** @var array $MAPPING_CSV */
    private $MAPPING_CSV = [
        // Household
        "address_street" => "A",
        "address_number" => "B",
        "address_postcode" => "C",
        "livelihood" => "D",
        "notes" => "E",
        "latitude" => "F",
        "longitude" => "G",
        "location" => [
            // Location
            "adm1" => "H",
            "adm2" => "I",
            "adm3" => "J",
            "adm4" => "K"
        ],
        // Beneficiary
        "beneficiaries" => [
            "given_name" => "L",
            "family_name" => "M",
            "gender" => "N",
            "status" => "O",
            "date_of_birth" => "P",
            "vulnerability_criteria" => "Q",
            "phones" => "R",
            "national_ids" => "S"
        ]
    ];


    public function __construct(EntityManagerInterface $entityManager, HouseholdService $householdService)
    {
        $this->em = $entityManager;
        $this->householdService = $householdService;
    }


    /**
     * Defined the reader and transform CSV to array
     *
     * @param $countryIso3
     * @param $project
     * @param UploadedFile $uploadedFile
     * @return array
     * @throws ValidationException
     * @throws \Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function saveCSV($countryIso3, $project, UploadedFile $uploadedFile)
    {
        // LOADING CSV
        $reader = new Csv();
        $reader->setDelimiter(",");
        $worksheet = $reader->load($uploadedFile->getRealPath())->getActiveSheet();
        $sheetArray = $worksheet->toArray(null, true, true, true);

        return $this->loadCSV($countryIso3, $project, $sheetArray);
    }

    /**
     * Transform the array to a list of household
     * On each household, found similar household already saved
     *
     * @param $countryIso3
     * @param $project
     * @param array $sheetArray
     * @return array
     * @throws ValidationException
     * @throws \Exception
     */
    public function loadCSV($countryIso3, $project, array $sheetArray)
    {
        // Get the list of households with their beneficiaries
        $listHouseholdsArray = $this->getListHouseholdArray($sheetArray, $countryIso3);

        $listHouseholdsSaved = $this->em->getRepository(Household::class)->findAll();

        $listHouseholdsWithSimilar = [];
        foreach ($listHouseholdsArray as $householdArray)
        {
            $listSimilarHouseholds = $this->getSimilarBeneficiary($householdArray, $listHouseholdsSaved);
            if (empty($listSimilarHouseholds))
                $this->householdService->create($householdArray, $project);
            else
                $listHouseholdsWithSimilar[] = [
                    "new" => $householdArray,
                    "old" => $listSimilarHouseholds
                ];
        }
        return $listHouseholdsWithSimilar;
    }

    /**
     * Return a list of household which are similar to the $newHouseholdarray
     * @param array $newHouseholdarray
     * @param array $listHousehold
     * @return array
     */
    private function getSimilarBeneficiary(array $newHouseholdarray, array $listHousehold)
    {
        // Concatenation of fields to compare with
        $stringToCompare = $newHouseholdarray["address_street"] .
            $newHouseholdarray["address_number"] .
            $newHouseholdarray["address_postcode"];

        $listSimilarHouseholds = [];
        /** @var Household $household */
        foreach ($listHousehold as $household)
        {
            similar_text($stringToCompare,
                $household->getAddressStreet() . $household->getAddressNumber() . $household->getAddressPostcode(),
                $percent
            );
            if ($this->percentSimilar < $percent)
            {
                $listSimilarHouseholds[] = $household;
            }
        }
        return $listSimilarHouseholds;
    }

    /**
     * Get the list of households with their beneficiaries
     * @param array $sheetArray
     * @param $countryIso3
     * @return array
     */
    private function getListHouseholdArray(array $sheetArray, $countryIso3)
    {
        // Get the mapping for the current country
        $mappingCSV = $this->loadMappingCSVOfCountry($countryIso3);
        $listHouseholdArray = [];
        $householdArray = null;
        $rowHeader = null;
        $formattedHouseholdArray = null;

        foreach ($sheetArray as $indexRow => $row)
        {
            if ($this->indexRowHeader === $indexRow)
                $rowHeader = $row;
            if ($indexRow < $this->first_row)
                continue;

            // Load the household array for the current row
            $formattedHouseholdArray = $this->mappingCSV($mappingCSV, $countryIso3, $row, $rowHeader);
            // Check if it's a new household or just a new beneficiary in the current row
            if ($formattedHouseholdArray["address_street"] !== "")
            {
                if (null !== $householdArray)
                {
                    $listHouseholdArray[] = $householdArray;
                }
                $householdArray = $formattedHouseholdArray;
            }
            else
            {
                $householdArray["beneficiaries"][] = current($formattedHouseholdArray["beneficiaries"]);
            }
        }

        if (null !== $formattedHouseholdArray)
        {
            $listHouseholdArray[] = $householdArray;
        }

        return $listHouseholdArray;
    }

    /**
     * Transform the array from the CSV (with index 'A', 'B') to a formatted array which can be compatible with the
     * function save of a household (with correct index names and correct deep array)
     *
     * @param array $mappingCSV
     * @param $countryIso3
     * @param array $row
     * @param array $rowHeader
     * @return array
     */
    private function mappingCSV(array $mappingCSV, $countryIso3, array $row, array $rowHeader)
    {
        $formattedHouseholdArray = [];
        foreach ($mappingCSV as $formattedIndex => $csvIndex)
        {
            if (is_array($csvIndex))
            {
                foreach ($csvIndex as $formattedIndex2 => $csvIndex2)
                {
                    $formattedHouseholdArray[$formattedIndex][$formattedIndex2] = strval($row[$csvIndex2]);
                }
            }
            else
            {
                $formattedHouseholdArray[$formattedIndex] = strval($row[$csvIndex]);
            }
        }
        // Add the country iso3 from the request
        $formattedHouseholdArray["location"]["country_iso3"] = $countryIso3;

        // Traitment on field with multiple value or foreign key inside (switch name to id for example)
        $this->fieldCountrySpecifics($mappingCSV, $formattedHouseholdArray, $rowHeader);
        $this->fieldVulnerabilityCriteria($formattedHouseholdArray);
        $this->fieldPhones($formattedHouseholdArray);
        $this->fieldNationalIds($formattedHouseholdArray);
        $this->fieldBeneficiary($formattedHouseholdArray);

        // ADD THE FIELD COUNTRY ONLY FOR THE CHECKING BY THE REQUEST VALIDATOR
        $formattedHouseholdArray["__country"] = $countryIso3;
        return $formattedHouseholdArray;
    }

    /**
     * Reformat the fields countries_specific_answers
     * @param array $mappingCSV
     * @param $formattedHouseholdArray
     * @param array $rowHeader
     */
    private function fieldCountrySpecifics(array $mappingCSV, &$formattedHouseholdArray, array $rowHeader)
    {
        $formattedHouseholdArray["country_specific_answers"] = [];
        foreach ($formattedHouseholdArray as $indexFormatted => $value)
        {
            if (substr($indexFormatted, 0, 20) === "tmp_country_specific")
            {
                $field = $rowHeader[$mappingCSV[$indexFormatted]];
                $countrySpecific = $this->em->getRepository(CountrySpecific::class)
                    ->findOneByField($field);
                $formattedHouseholdArray["country_specific_answers"][] = [
                    "answer" => $value,
                    "country_specific" => ["id" => $countrySpecific->getId()]
                ];
                unset($formattedHouseholdArray[$indexFormatted]);
            }
        }
    }

    /**
     * Reformat the field which contains vulnerability criteria => switch list of names to a list of ids
     * @param $formattedHouseholdArray
     */
    private function fieldVulnerabilityCriteria(&$formattedHouseholdArray)
    {
        $vulnerability_criteria_string = $formattedHouseholdArray["beneficiaries"]["vulnerability_criteria"];
        $vulnerability_criteria_array = array_map('trim', explode(";", $vulnerability_criteria_string));
        $formattedHouseholdArray["beneficiaries"]["vulnerability_criteria"] = [];
        foreach ($vulnerability_criteria_array as $item)
        {
            $vulnerability_criterion = $this->em->getRepository(VulnerabilityCriterion::class)->findOneByValue($item);
            if (!$vulnerability_criterion instanceof VulnerabilityCriterion)
                continue;
            $formattedHouseholdArray["beneficiaries"]["vulnerability_criteria"][] = ["id" => $vulnerability_criterion->getId()];
        }
    }

    /**
     * Reformat the field phones => switch string 'type-number' to [type => type, number => number]
     * @param $formattedHouseholdArray
     */
    private function fieldPhones(&$formattedHouseholdArray)
    {
        $phones_string = $formattedHouseholdArray["beneficiaries"]["phones"];
        $phones_array = array_map('trim', explode(";", $phones_string));
        $formattedHouseholdArray["beneficiaries"]["phones"] = [];
        foreach ($phones_array as $item)
        {
            $item_array = array_map('trim', explode("-", $item));
            $formattedHouseholdArray["beneficiaries"]["phones"][] = ["type" => $item_array[0], "number" => $item_array[1]];
        }
    }

    /**
     * Reformat the field nationalids => switch string 'idtype-idnumber' to [id_type => idtype, id_number => idnumber]
     * @param $formattedHouseholdArray
     */
    private function fieldNationalIds(&$formattedHouseholdArray)
    {
        $national_ids_string = $formattedHouseholdArray["beneficiaries"]["national_ids"];
        $national_ids_array = array_map('trim', explode(";", $national_ids_string));
        $formattedHouseholdArray["beneficiaries"]["national_ids"] = [];
        foreach ($national_ids_array as $item)
        {
            $item_array = array_map('trim', explode("-", $item));
            $formattedHouseholdArray["beneficiaries"]["national_ids"][] = ["id_type" => $item_array[0], "id_number" => $item_array[1]];
        }
    }

    /**
     * Load the mapping CSV for a specific country. Some columns can move because on the number of country specifics
     *
     * @param $countryIso3
     * @return array
     */
    private function loadMappingCSVOfCountry($countryIso3)
    {
        $countrySpecifics = $this->em->getRepository(CountrySpecific::class)->findByCountryIso3($countryIso3);
        // Get the number of country specific for the specific country countryIso3
        $nbCountrySpecific = count($countrySpecifics);
        $mappingCSVCountry = [];
        $countrySpecificsAreLoaded = false;
        foreach ($this->MAPPING_CSV as $indexFormatted => $indexCSV)
        {
            // For recursive array (allowed only 1 level of recursivity)
            if (is_array($indexCSV))
            {
                foreach ($indexCSV as $indexFormatted2 => $indexCSV2)
                {
                    // If the column is before the non-static columns, change nothing
                    if ($indexCSV2 < $this->firstColumnNonStatic)
                        $mappingCSVCountry[$indexFormatted][$indexFormatted2] = $indexCSV2;
                    // Else we increment the column.
                    // Example : if $nbCountrySpecific = 1, we shift the column by 1 (if the column is X, it will became Y)
                    else
                    {
                        // If we have not added the country specific column in the mapping
                        if (!$countrySpecificsAreLoaded)
                        {
                            // Add each country specific column in the mapping
                            for ($i = 0; $i < $nbCountrySpecific; $i++)
                            {
                                $mappingCSVCountry["tmp_country_specific" . $i] =
                                    $this->SUMOfLetter($indexCSV2, $i);
                            }
                            $countrySpecificsAreLoaded = true;
                        }
                        $mappingCSVCountry[$indexFormatted][$indexFormatted2] = $this->SUMOfLetter($indexCSV2, $nbCountrySpecific);
                    }
                }
            }
            else
            {
                // Same process than in the if
                if ($indexCSV < $this->firstColumnNonStatic)
                    $mappingCSVCountry[$indexFormatted] = $indexCSV;
                else
                {
                    // If we have not added the country specific column in the mapping
                    if (!$countrySpecificsAreLoaded)
                    {
                        // Add each country specific column in the mapping
                        for ($i = 0; $i < $nbCountrySpecific; $i++)
                        {
                            $mappingCSVCountry["tmp_country_specific" . $i] =
                                $this->SUMOfLetter($indexCSV, $i);
                        }
                        $countrySpecificsAreLoaded = true;
                    }
                    $mappingCSVCountry[$indexFormatted] = $this->SUMOfLetter($indexCSV, $nbCountrySpecific);
                }
            }
        }

        return $mappingCSVCountry;
    }

    /**
     * Reformat the field beneficiary
     * @param $formattedHouseholdArray
     */
    private function fieldBeneficiary(&$formattedHouseholdArray)
    {
        $beneficiary = $formattedHouseholdArray["beneficiaries"];
        $beneficiary["profile"] = ["photo" => ""];
        $beneficiary["updated_on"] = (new \DateTime())->format('Y-m-d H:m:i');
        unset($formattedHouseholdArray["beneficiaries"]);
        $formattedHouseholdArray["beneficiaries"][] = $beneficiary;
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