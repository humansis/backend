<?php


namespace BeneficiaryBundle\Utils\Mapper;


use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;

class CSVToArrayMapper extends AbstractMapper
{

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
        $rowHeader = [];
        $formattedHouseholdArray = null;

        foreach ($sheetArray as $indexRow => $row)
        {
            if (!$row['A'] && !$row['B'] && !$row['C'] && !$row['D'] && !$row['E'] && !$row['F'] && !$row['G'] && !$row['H'] && !$row['I'] && !$row['J'] && !$row['K'] && !$row['L'] && !$row['M'] && !$row['N'] && !$row['O'] && !$row['P'] && !$row['Q'] && !$row['R'] && !$row['S'] && !$row['T'] && !$row['U'] && !$row['V'] && !$row['W'] && !$row['X'])
                continue;

            //Index == 2
            if (Household::indexRowHeader === $indexRow)
                $rowHeader = $row;
            //Index < 4
            if ($indexRow < Household::firstRow)
                continue;

            // Load the household array for the current row
            try
            {
                $formattedHouseholdArray = $this->mappingCSV($mappingCSV, $countryIso3, $indexRow, $row, $rowHeader);
            }
            catch (\Exception $exception)
            {
                throw new \Exception($exception->getMessage());
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
     * @param int $lineNumber
     * @param array $row
     * @param array $rowHeader
     * @return array
     * @throws \Exception
     */
    private function mappingCSV(array $mappingCSV, $countryIso3, int $lineNumber, array $row, array $rowHeader)
    {
        $formattedHouseholdArray = [];
        foreach ($mappingCSV as $formattedIndex => $csvIndex)
        {
            if (is_array($csvIndex))
            {
                foreach ($csvIndex as $formattedIndex2 => $csvIndex2)
                {
                    if ($row[$mappingCSV['beneficiaries']['given_name']] == null
                        || $row[$mappingCSV['beneficiaries']['family_name']] == null
                        || (explode('.', $row[$mappingCSV['beneficiaries']['gender']])[0] != 'Female' && explode('.', $row[$mappingCSV['beneficiaries']['gender']])[0] != 'Male')
                        || (explode('.', $row[$mappingCSV['beneficiaries']['status']])[0] != '0' && explode('.', $row[$mappingCSV['beneficiaries']['status']])[0] != '1')
                        || $row[$mappingCSV['beneficiaries']['date_of_birth']] == null) {
                        if ($row[$mappingCSV['beneficiaries']['given_name']] == null) {
                            throw new \Exception("There is missing information at the column " . $mappingCSV['beneficiaries']['given_name'] . " at the line " . $lineNumber);
                        }
                        elseif ($row[$mappingCSV['beneficiaries']['family_name']] == null) {
                            throw new \Exception("There is missing information at the column " . $mappingCSV['beneficiaries']['family_name'] . " at the line " . $lineNumber);
                        }
                        elseif (explode('.', $row[$mappingCSV['beneficiaries']['gender']])[0] != 'Female' && explode('.', $row[$mappingCSV['beneficiaries']['gender']])[0] != 'Male') {
                            throw new \Exception("There is missing information at the column " . $mappingCSV['beneficiaries']['gender'] . " at the line " . $lineNumber);
                        }
                        elseif ((explode('.', $row[$mappingCSV['beneficiaries']['status']])[0] != '0' && explode('.', $row[$mappingCSV['beneficiaries']['status']])[0] != '1')) {
                            throw new \Exception("There is missing information at the column " . $mappingCSV['beneficiaries']['status'] . " at the line " . $lineNumber);
                        }
                        elseif ($row[$mappingCSV['beneficiaries']['date_of_birth']] == null) {
                            throw new \Exception("There is missing information at the column " . $mappingCSV['beneficiaries']['date_of_birth'] . " at the line " . $lineNumber);
                        }
                    }
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
        $types_string = $formattedHouseholdArray["beneficiaries"]["type"];
        $phones_string = $formattedHouseholdArray["beneficiaries"]["phones"];
        $proxy_string = $formattedHouseholdArray["beneficiaries"]["proxy"];
        $phones_array = array_map('trim', explode(";", $phones_string));
        $types_array = array_map('trim', explode(";", $types_string));

        $formattedHouseholdArray["beneficiaries"]["phones"] = [];
        foreach ($phones_array as $index => $item)
        {
            if ("" == $item)
                continue;


            $formattedHouseholdArray["beneficiaries"]["phones"][] = ["type" => $types_array[$index], "number" => $item, 'proxy' => $proxy_string];
        }
    }

    /**
     * Reformat the field nationalids => switch string 'idtype-idnumber' to [id_type => idtype, id_number => idnumber]
     * @param $formattedHouseholdArray
     */
    private function fieldNationalIds(&$formattedHouseholdArray)
    {
        $types_national_ids = $formattedHouseholdArray["beneficiaries"]["id_type"];
        $national_ids_string = $formattedHouseholdArray["beneficiaries"]["national_ids"];
        $national_ids_array = array_map('trim', explode(";", $national_ids_string));
        $types_national_ids_array = array_map('trim', explode(";", $types_national_ids));
        $formattedHouseholdArray["beneficiaries"]["national_ids"] = [];
        foreach ($national_ids_array as $index => $item)
        {
            if ("" == $item)
                continue;

            $formattedHouseholdArray["beneficiaries"]["national_ids"][] = ["id_type" => $types_national_ids_array[$index], "id_number" => $item];
        }
    }

    /**
     * Reformat the field beneficiary
     * @param $formattedHouseholdArray
     */
    private function fieldBeneficiary(&$formattedHouseholdArray)
    {
        $beneficiary = $formattedHouseholdArray["beneficiaries"];
        $beneficiary["profile"] = ["photo" => ""];
        unset($formattedHouseholdArray["beneficiaries"]);
        $formattedHouseholdArray["beneficiaries"][] = $beneficiary;
    }
}