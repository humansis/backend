<?php


namespace BeneficiaryBundle\Utils;


use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Mapper
{

    /** @var EntityManagerInterface $em */
    private $em;

    /**
     * The row index of the header (with the name of country specifics)
     * @var int
     */
    private $indexRowHeader = 2;

    /**
     * First value with a column in the csv which can move, depends on the number of country specifics
     * @var string
     */
    private $firstColumnNonStatic = 'L';

    /**
     * @var array $MAPPING_CSV
     */
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

    /**
     * First row with data
     * @var int $first_row
     */
    private $first_row = 3;


    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }


    /**
     * Get the list of households with their beneficiaries
     * @param array $sheetArray
     * @param $countryIso3
     * @return array
     * @throws \Exception
     */
    public function fromCSVToArray(array $sheetArray, $countryIso3)
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
            try
            {
                $formattedHouseholdArray = $this->mappingCSV($mappingCSV, $countryIso3, $row, $rowHeader);
            }
            catch (\Exception $exception)
            {
                throw new \Exception("Your pattern file is not correct. Please used the last template of your country.");
            }
            // Check if it's a new household or just a new beneficiary in the current row
            if ($formattedHouseholdArray["address_street"] !== null)
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
     * Get the list of households with their beneficiaries
     * @param array $sheetArray
     * @param $countryIso3
     * @return array
     * @throws \Exception
     */
    public function fromHouseholdToCSV(Worksheet &$worksheet, array $listHouseholds, $countryIso3)
    {
        // Get the mapping for the current country
        $mappingCSV = $this->loadMappingCSVOfCountry($countryIso3);
        dump($mappingCSV);
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
            try
            {
                $formattedHouseholdArray = $this->mappingCSV($mappingCSV, $countryIso3, $row, $rowHeader);
            }
            catch (\Exception $exception)
            {
                throw new \Exception("Your pattern file is not correct. Please used the last template of your country.");
            }
            // Check if it's a new household or just a new beneficiary in the current row
            if ($formattedHouseholdArray["address_street"] !== null)
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
     * @throws \Exception
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
                    if (null !== $row[$csvIndex2])
                        $row[$csvIndex2] = strval($row[$csvIndex2]);
                    $formattedHouseholdArray[$formattedIndex][$formattedIndex2] = $row[$csvIndex2];
                }
            }
            else
            {
                if (null !== $row[$csvIndex])
                    $row[$csvIndex] = strval($row[$csvIndex]);
                $formattedHouseholdArray[$formattedIndex] = $row[$csvIndex];
            }
        }
        // Add the country iso3 from the request
        $formattedHouseholdArray["location"]["country_iso3"] = $countryIso3;

        // Traitment on field with multiple value or foreign key inside (switch name to id for example)
        try
        {
            $this->fieldCountrySpecifics($mappingCSV, $formattedHouseholdArray, $rowHeader);
            $this->fieldVulnerabilityCriteria($formattedHouseholdArray);
            $this->fieldPhones($formattedHouseholdArray);
            $this->fieldNationalIds($formattedHouseholdArray);
            $this->fieldBeneficiary($formattedHouseholdArray);
        }
        catch (\Exception $exception)
        {
            throw new \Exception("Your file is not correctly formatted for this country ($countryIso3)");
        }
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
                    ->findOneByFieldString($field);
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
            $vulnerability_criterion = $this->em->getRepository(VulnerabilityCriterion::class)->findOneByFieldString($item);
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
            if ("" == $item)
                continue;
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
            if ("" == $item)
                continue;
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