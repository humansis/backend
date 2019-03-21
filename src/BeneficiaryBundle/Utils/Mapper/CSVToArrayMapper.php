<?php

namespace BeneficiaryBundle\Utils\Mapper;

use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;

class CSVToArrayMapper extends AbstractMapper
{
    /**
     * Get the list of households with their beneficiaries.
     *
     * @param array $sheetArray
     * @param $countryIso3
     *
     * @return array
     *
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

        foreach ($sheetArray as $indexRow => $row) {
            if (!$row['A'] && !$row['B'] && !$row['C'] && !$row['D'] && !$row['E'] && !$row['F'] && !$row['G'] && !$row['H'] && !$row['I'] && !$row['J'] && !$row['K'] && !$row['L'] && !$row['M'] && !$row['N'] && !$row['O'] && !$row['P'] && !$row['Q'] && !$row['R'] && !$row['S'] && !$row['T'] && !$row['U'] && !$row['V'] && !$row['W'] && !$row['X'] && !$row['Y'] && !$row['Z'] && !$row['AA']) {
                continue;
            }

            //Index == 2
            if (Household::indexRowHeader === $indexRow) {
                $rowHeader = $row;
            }
            //Index < 4
            if ($indexRow < Household::firstRow) {
                continue;
            }

            // Load the household array for the current row
            try {
                $formattedHouseholdArray = $this->mappingCSV($mappingCSV, $countryIso3, $indexRow, $row, $rowHeader);
            } catch (\Exception $exception) {
                throw new \Exception($exception->getMessage());
            }
            // Check if it's a new household or just a new beneficiary in the current row
            if ($formattedHouseholdArray['address_street'] !== null) {
                if (null !== $householdArray) {
                    $listHouseholdArray[] = $householdArray;
                }
                $householdArray = $formattedHouseholdArray;
            } else {
                $householdArray['beneficiaries'][] = current($formattedHouseholdArray['beneficiaries']);
            }
        }

        if (null !== $formattedHouseholdArray) {
            $listHouseholdArray[] = $householdArray;
        }

        return $listHouseholdArray;
    }

    /**
     * Transform the array from the CSV (with index 'A', 'B') to a formatted array which can be compatible with the
     * function save of a household (with correct index names and correct deep array).
     *
     * @param array $mappingCSV
     * @param $countryIso3
     * @param int   $lineNumber
     * @param array $row
     * @param array $rowHeader
     *
     * @return array
     *
     * @throws \Exception
     */
    private function mappingCSV(array $mappingCSV, $countryIso3, int $lineNumber, array $row, array $rowHeader)
    {
        $formattedHouseholdArray = [];
        foreach ($mappingCSV as $formattedIndex => $csvIndex) {
            if (is_array($csvIndex)) {
                foreach ($csvIndex as $formattedIndex2 => $csvIndex2) {

                    // Retrieve the beneficiary's information from the array
                    $givenName = $row[$mappingCSV['beneficiaries']['given_name']];
                    $familyName = $row[$mappingCSV['beneficiaries']['family_name']];
                    $gender = $row[$mappingCSV['beneficiaries']['gender']];
                    $dateOfBirth = $row[$mappingCSV['beneficiaries']['date_of_birth']];
                    $status = $row[$mappingCSV['beneficiaries']['status']];
                    $residencyStatus = $row[$mappingCSV['beneficiaries']['residency_status']];

                    // Verify that there are no missing information in each beneficiary
                    if ($givenName == null
                        || $familyName == null
                        || (explode('.', $gender)[0] != 'Female' && explode('.', $gender)[0] != 'Male')
                        || (explode('.', $status)[0] != '0' && explode('.', $status)[0] != '1')
                        || $dateOfBirth == null
                        || $residencyStatus == null) {
                        if ($givenName == null) {
                            throw new \Exception('There is missing information at the column '.$mappingCSV['beneficiaries']['given_name'].' at the line '.$lineNumber);
                        } elseif ($familyName == null) {
                            throw new \Exception('There is missing information at the column '.$mappingCSV['beneficiaries']['family_name'].' at the line '.$lineNumber);
                        } elseif (explode('.', $gender)[0] != 'Female' && explode('.', $gender)[0] != 'Male') {
                            throw new \Exception('There is missing information at the column '.$mappingCSV['beneficiaries']['gender'].' at the line '.$lineNumber);
                        } elseif ((explode('.', $status)[0] != '0' && explode('.', $status)[0] != '1')) {
                            throw new \Exception('There is missing information at the column '.$mappingCSV['beneficiaries']['status'].' at the line '.$lineNumber);
                        } elseif ($dateOfBirth == null) {
                            throw new \Exception('There is missing information at the column '.$mappingCSV['beneficiaries']['date_of_birth'].' at the line '.$lineNumber);
                        } elseif ($residencyStatus == null) {
                            throw new \Exception('There is missing information at the column '.$mappingCSV['beneficiaries']['residency_status'].' at the line '.$lineNumber);
                        }
                    }

                    // Check that residencyStatus has one of the authorized values
                    $authorizedResidencyStatus = ['refugee', 'IDP', 'resident'];
                    if (!in_array(strtolower($residencyStatus), $authorizedResidencyStatus)) {
                        throw new \Exception('Your residency status must be either refugee, IDP or resident');
                    }

                    // Check that the year of birth is between 1900 and today
                    $yearOfBirth = intval(explode('-', $dateOfBirth)[0]);
                    if ($yearOfBirth < 1900 || $yearOfBirth > intval(date('Y'))) {
                        throw new \Exception('Your year of birth can not be before 1900 or after the current year');
                    }
                    if (null !== $row[$csvIndex2]) {
                        $row[$csvIndex2] = strval($row[$csvIndex2]);
                    }

                    $formattedHouseholdArray[$formattedIndex][$formattedIndex2] = $row[$csvIndex2];
                }
            } else {
                if (null !== $row[$csvIndex]) {
                    $row[$csvIndex] = strval($row[$csvIndex]);
                }

                $formattedHouseholdArray[$formattedIndex] = $row[$csvIndex];
            }
        }
        // Add the country iso3 from the request
        $formattedHouseholdArray['location']['country_iso3'] = $countryIso3;

        // Treatment on field with multiple value or foreign key inside (switch name to id for example)
        try {
            $this->fieldCountrySpecifics($mappingCSV, $formattedHouseholdArray, $rowHeader);
            $this->fieldVulnerabilityCriteria($formattedHouseholdArray);
            $this->fieldPhones($formattedHouseholdArray);
            $this->fieldNationalIds($formattedHouseholdArray);
            $this->fieldBeneficiary($formattedHouseholdArray);
        } catch (\Exception $exception) {
            throw new \Exception("Your file is not correctly formatted for this country ($countryIso3)");
        }
        // ADD THE FIELD COUNTRY ONLY FOR THE CHECKING BY THE REQUEST VALIDATOR
        $formattedHouseholdArray['__country'] = $countryIso3;

        return $formattedHouseholdArray;
    }

    /**
     * Reformat the fields countries_specific_answers.
     *
     * @param array $mappingCSV
     * @param $formattedHouseholdArray
     * @param array $rowHeader
     */
    private function fieldCountrySpecifics(array $mappingCSV, &$formattedHouseholdArray, array $rowHeader)
    {
        $formattedHouseholdArray['country_specific_answers'] = [];
        foreach ($formattedHouseholdArray as $indexFormatted => $value) {
            if (substr($indexFormatted, 0, 20) === 'tmp_country_specific') {
                $field = $rowHeader[$mappingCSV[$indexFormatted]];
                $countrySpecific = $this->em->getRepository(CountrySpecific::class)
                    ->findOneByFieldString($field);
                $formattedHouseholdArray['country_specific_answers'][] = [
                    'answer' => $value,
                    'country_specific' => ['id' => $countrySpecific->getId()],
                ];
                unset($formattedHouseholdArray[$indexFormatted]);
            }
        }
    }

    /**
     * Reformat the field which contains vulnerability criteria => switch list of names to a list of ids.
     *
     * @param $formattedHouseholdArray
     */
    private function fieldVulnerabilityCriteria(&$formattedHouseholdArray)
    {
        $vulnerability_criteria_string = $formattedHouseholdArray['beneficiaries']['vulnerability_criteria'];
        $vulnerability_criteria_array = array_map('trim', explode(';', $vulnerability_criteria_string));
        $formattedHouseholdArray['beneficiaries']['vulnerability_criteria'] = [];
        foreach ($vulnerability_criteria_array as $item) {
            $vulnerability_criterion = $this->em->getRepository(VulnerabilityCriterion::class)->findOneByFieldString($item);
            if (!$vulnerability_criterion instanceof VulnerabilityCriterion) {
                continue;
            }
            $formattedHouseholdArray['beneficiaries']['vulnerability_criteria'][] = ['id' => $vulnerability_criterion->getId()];
        }
    }

    /**
     * Reformat the field phones => switch string 'type-number' to [type => type, number => number].
     *
     * @param $formattedHouseholdArray
     */
    private function fieldPhones(&$formattedHouseholdArray)
    {
        $types1_string = $formattedHouseholdArray['beneficiaries']['phone1_type'];
        $phone1_prefix_string = $formattedHouseholdArray['beneficiaries']['phone1_prefix'];
        $phone1_number_string = $formattedHouseholdArray['beneficiaries']['phone1_number'];
        $phone1_proxy_string = $formattedHouseholdArray['beneficiaries']['phone1_proxy'];

        $phone1_prefix_string = str_replace("'", '', $phone1_prefix_string);
        $phone1_number_string = str_replace("'", '', $phone1_number_string);

        $formattedHouseholdArray['beneficiaries']['phones'] = [];
        array_push($formattedHouseholdArray['beneficiaries']['phones'], array('type' => $types1_string, 'prefix' => $phone1_prefix_string, 'number' => $phone1_number_string, 'proxy' => $phone1_proxy_string));

        if (key_exists('phone2_type', $formattedHouseholdArray['beneficiaries'])) {
            $phone2_type_string = $formattedHouseholdArray['beneficiaries']['phone2_type'];
            $phone2_prefix_string = $formattedHouseholdArray['beneficiaries']['phone2_prefix'];
            $phone2_number_string = $formattedHouseholdArray['beneficiaries']['phone2_number'];
            $phone2_proxy_string = $formattedHouseholdArray['beneficiaries']['phone2_proxy'];

            $phone2_prefix_string = str_replace("'", '', $phone2_prefix_string);
            $phone2_number_string = str_replace("'", '', $phone2_number_string);

            array_push($formattedHouseholdArray['beneficiaries']['phones'], ['type' => $phone2_type_string, 'prefix' => $phone2_prefix_string, 'number' => $phone2_number_string, 'proxy' => $phone2_proxy_string]);
        }
    }

    /**
     * Reformat the field nationalids => switch string 'idtype-idnumber' to [id_type => idtype, id_number => idnumber].
     *
     * @param $formattedHouseholdArray
     */
    private function fieldNationalIds(&$formattedHouseholdArray)
    {
        $type_national_id = $formattedHouseholdArray['beneficiaries']['national_id_type'];
        $national_id_string = $formattedHouseholdArray['beneficiaries']['national_id_number'];
        $formattedHouseholdArray['beneficiaries']['national_ids'] = [];
        if ($national_id_string != '') {
            $formattedHouseholdArray['beneficiaries']['national_ids'][] = ['id_type' => $type_national_id, 'id_number' => $national_id_string];
        }
    }

    /**
     * Reformat the field beneficiary.
     *
     * @param $formattedHouseholdArray
     */
    private function fieldBeneficiary(&$formattedHouseholdArray)
    {
        $beneficiary = $formattedHouseholdArray['beneficiaries'];
        $beneficiary['profile'] = ['photo' => ''];
        unset($formattedHouseholdArray['beneficiaries']);
        $formattedHouseholdArray['beneficiaries'][] = $beneficiary;
    }
}
