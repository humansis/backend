<?php

namespace BeneficiaryBundle\Utils\Mapper;

use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
use BeneficiaryBundle\Entity\Camp;
use BeneficiaryBundle\Entity\HouseholdLocation;

class CSVToArrayMapper extends AbstractMapper
{
    private $countrySpecificIds = [];

    private $vulnerabilityCriteriaIds = [];

    private $adms = [];

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
            // Check if no column has been deleted
            if (!$row['A'] && !$row['B'] && !$row['C'] && !$row['D'] && !$row['E'] && !$row['F'] && !$row['G'] && !$row['H'] && !$row['I'] && !$row['J'] && !$row['K'] && !$row['L'] && !$row['M'] && !$row['N'] && !$row['O'] && !$row['P'] && !$row['Q'] && !$row['R'] && !$row['S'] && !$row['T'] && !$row['U'] && !$row['V'] && !$row['W'] && !$row['X'] && !$row['Y'] && !$row['Z'] && !$row['AA'] && !$row['AB'] && !$row['AC'] && !$row['AD'] && !$row['AE']) {
                continue;
            }

            // Index == 1
            if (Household::indexRowHeader === $indexRow) {
                $rowHeader = $row;
            }
            // Index < first row of data
            if ($indexRow < Household::firstRow) {
                continue;
            }

            // Load the household array for the current row
            try {
                $formattedHouseholdArray = $this->mappingCSV($mappingCSV, $countryIso3, $indexRow, $row, $rowHeader);
            } catch (\Exception $exception) {
                throw $exception;
            }
            // Check if it's a new household or just a new beneficiary in the current household
            // If address_street exists it's a new household
            if (array_key_exists('household_locations', $formattedHouseholdArray)) {
                // If there is already a previous household, add it to the list of households and create a new one
                if (null !== $householdArray) {
                    $listHouseholdArray[] = $householdArray;
                }
                $householdArray = $formattedHouseholdArray;
                $householdArray['beneficiaries'] = [$formattedHouseholdArray['beneficiaries']];
                $this->generateStaticBeneficiary($householdArray);
            } else {
                // Add beneficiary to existing household
                $householdArray['beneficiaries'][] = $formattedHouseholdArray['beneficiaries'];
            }
        }
        // Add the last household to the list
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
                    $enGivenName = $row[$mappingCSV['beneficiaries']['en_given_name']];
                    $enFamilyName = $row[$mappingCSV['beneficiaries']['en_family_name']];
                    $localGivenName = $row[$mappingCSV['beneficiaries']['local_given_name']];
                    $localFamilyName = $row[$mappingCSV['beneficiaries']['local_family_name']];
                    $gender = $row[$mappingCSV['beneficiaries']['gender']];
                    $dateOfBirth = $row[$mappingCSV['beneficiaries']['date_of_birth']];
                    $status = $row[$mappingCSV['beneficiaries']['status']];
                    $residencyStatus = $row[$mappingCSV['beneficiaries']['residency_status']];

                    // Verify that there are no missing information in each beneficiary
                    if ($localGivenName == null) {
                        throw new \Exception('There is missing/incorrect information at the column '.$mappingCSV['beneficiaries']['local_given_name'].' at the line '.$lineNumber);
                    } elseif ($localFamilyName == null) {
                        throw new \Exception('There is missing/incorrect information at the column '.$mappingCSV['beneficiaries']['local_family_name'].' at the line '.$lineNumber);
                    } elseif (strcasecmp(trim($gender), 'Female') !== 0 && strcasecmp(trim($gender), 'Male') !== 0 &&
                        strcasecmp(trim($gender), 'F') !== 0 && strcasecmp(trim($gender), 'M') !== 0) {
                        throw new \Exception('There is missing/incorrect information at the column '.$mappingCSV['beneficiaries']['gender'].' at the line '.$lineNumber);
                    } elseif (($status !== 'true' && $status !== 'false')) {
                        throw new \Exception('There is missing/incorrect information at the column '.$mappingCSV['beneficiaries']['status'].' at the line '.$lineNumber);
                    } elseif ($dateOfBirth == null) {
                        throw new \Exception('There is missing/incorrect information at the column '.$mappingCSV['beneficiaries']['date_of_birth'].' at the line '.$lineNumber);
                    } elseif ($residencyStatus == null) {
                        throw new \Exception('There is missing/incorrect information at the column '.$mappingCSV['beneficiaries']['residency_status'].' at the line '.$lineNumber);
                    }

                    // Check that residencyStatus has one of the authorized values
                    $authorizedResidencyStatus = ['refugee', 'IDP', 'resident'];
                    // Add case insensitivity
                    $statusIsAuthorized = false;
                    foreach ($authorizedResidencyStatus as $status) {
                        if (strcasecmp($status, $residencyStatus)) {
                            $residencyStatus = $status;
                            $statusIsAuthorized = true;
                        }
                    }
                    if (!$statusIsAuthorized) {
                        throw new \Exception('Your residency status must be either refugee, IDP or resident');
                    }

                    // Check that the year of birth is between 1900 and today
                    if (strrpos($dateOfBirth, '-') !== false) {
                        $yearOfBirth = intval(explode('-', $dateOfBirth)[2]);
                    } elseif (strrpos($dateOfBirth, '/') !== false) {
                        $yearOfBirth = intval(explode('/', $dateOfBirth)[2]);
                    } else {
                        throw new \Exception('The date is not properly formatted in dd-mm-YYYY format');
                    }
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

        if ($formattedHouseholdArray['income_level'] && !in_array($formattedHouseholdArray['income_level'], [1,2,3,4,5])) {
            throw new \Exception('The income level must be between 1 and 5');
        }

        $this->mapLocation($formattedHouseholdArray);

        if ($formattedHouseholdArray['camp']) {
            if (!$formattedHouseholdArray['tent_number']) {
                throw new \Exception('You have to enter a tent number');
            }
            $campName = $formattedHouseholdArray['camp'];
            $formattedHouseholdArray['household_locations'] = [
                [
                    'location_group' => HouseholdLocation::LOCATION_GROUP_CURRENT,
                    'type' => HouseholdLocation::LOCATION_TYPE_CAMP,
                    'camp_address' => [
                        'camp' => [
                            'id' => null,
                            'name' => $campName,
                            'location' => $formattedHouseholdArray['location']
                        ],
                        'tent_number' =>  $formattedHouseholdArray['tent_number'],
                    ]
                ]
            ];
            $alreadyExistingCamp = $this->em->getRepository(Camp::class)->findByNameAndLocation($campName, $formattedHouseholdArray['location']);
            if ($alreadyExistingCamp) {
                $formattedHouseholdArray['household_locations'][0]['camp_address']['camp']['id'] = $alreadyExistingCamp->getId();
            }
        } else if ($formattedHouseholdArray['address_number']) {
            if (!$formattedHouseholdArray['address_street'] || !$formattedHouseholdArray['address_postcode']) {
                throw new \Exception('The address is invalid');
            }
            $formattedHouseholdArray['household_locations'] = [
                [
                    'location_group' => HouseholdLocation::LOCATION_GROUP_CURRENT,
                    'type' => HouseholdLocation::LOCATION_TYPE_RESIDENCE,
                    'address' => [
                        'number' => $formattedHouseholdArray['address_number'],
                        'street' =>  $formattedHouseholdArray['address_street'],
                        'postcode' =>  $formattedHouseholdArray['address_postcode'],
                        'location' => $formattedHouseholdArray['location']
                    ]
                ]
            ];
        }

        unset($formattedHouseholdArray['location']);
        unset($formattedHouseholdArray['address_number']);
        unset($formattedHouseholdArray['address_street']);
        unset($formattedHouseholdArray['address_postcode']);
        unset($formattedHouseholdArray['camp']);
        unset($formattedHouseholdArray['tent_number']);

        // Treatment on field with multiple value or foreign key inside (switch name to id for example)
        try {
            $this->mapCountrySpecifics($mappingCSV, $formattedHouseholdArray, $rowHeader);
            $this->mapVulnerabilityCriteria($formattedHouseholdArray);
            $this->mapPhones($formattedHouseholdArray);
            $this->mapGender($formattedHouseholdArray);
            $this->mapNationalIds($formattedHouseholdArray);
            $this->mapProfile($formattedHouseholdArray);
            $this->mapStatus($formattedHouseholdArray);
            $this->mapLivelihood($formattedHouseholdArray);
            $formattedHouseholdArray['coping_strategies_index'] = null;
            $formattedHouseholdArray['food_consumption_score'] = null;
        } catch (\Exception $exception) {
            throw $exception;
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
    private function mapCountrySpecifics(array $mappingCSV, &$formattedHouseholdArray, array $rowHeader)
    {
        $formattedHouseholdArray['country_specific_answers'] = [];
        foreach ($formattedHouseholdArray as $indexFormatted => $value) {
            if (substr($indexFormatted, 0, 20) === 'tmp_country_specific') {
                $field = $rowHeader[$mappingCSV[$indexFormatted]];
                $formattedHouseholdArray['country_specific_answers'][] = [
                    'answer' => $value,
                    'country_specific' => ['id' => $this->getCountrySpecificId($field)],
                ];
                unset($formattedHouseholdArray[$indexFormatted]);
            }
        }
    }

    private function generateStaticBeneficiary(&$householdArray)
    {
        $staticFields = [
            'f-0-2', 'f-2-5', 'f-6-17', 'f-18-64', 'f-65-65',
            'm-0-2', 'm-2-5', 'm-6-17', 'm-18-64', 'm-65-65',
        ];
        foreach ($staticFields as $staticField) {
            $field = 'member_' . $staticField;
            $headBeneficiary = $householdArray['beneficiaries'][0];
            if ($headBeneficiary && !empty($householdArray[$field])) {
                list ($gender, $fromAge, $toAge) = explode('-', $staticField);
                $gender = $gender === 'f' ? 0 : 1;
                $birthDate = new \DateTime();
                $ageInterval = new \DateInterval('P' . ($toAge - $fromAge) * 12 . 'M') ;
                $birthDate->sub($ageInterval);
                for ($i = 1; $i <= $householdArray[$field]; $i++) {
                    $generatedBeneficiary = [
                        'local_given_name' => 'Member ' . $i,
                        'local_family_name' => $headBeneficiary['local_family_name'],
                        'en_given_name' => 'Member ' . $i,
                        'en_family_name' => $headBeneficiary['en_family_name'],
                        'date_of_birth' => $birthDate->format('d-m-Y'),
                        'gender' => $gender,
                        'status' => 0,
                        'residency_status' => $headBeneficiary['residency_status'],
                        'vulnerability_criteria' => [],
                        'phones' => [],
                        'national_ids' => [],
                        'profile' => [
                            'photo' => '',
                        ],
                    ];
                    $householdArray['beneficiaries'][] = $generatedBeneficiary;
                }
            }
            unset($householdArray[$field]);
        }
    }

    /**
     * Returns the id of the CountrySpecific passed in parameter
     *
     * @param string $field
     * @return int
     */
    private function getCountrySpecificId(string $field) : int
    {
        if (! array_key_exists($field, $this->countrySpecificIds)) {
            $repo = $this->em->getRepository(CountrySpecific::class);
            $this->countrySpecificIds[$field] = $repo->findOneByFieldString($field)->getId();
        }

        return $this->countrySpecificIds[$field];
    }

    /**
     * Reformat the field which contains vulnerability criteria => switch list of names to a list of ids.
     *
     * @param $formattedHouseholdArray
     */
    private function mapVulnerabilityCriteria(&$formattedHouseholdArray)
    {
        $vulnerability_criteria_string = $formattedHouseholdArray['beneficiaries']['vulnerability_criteria'];

        // We are separating the vulnerability criteria in the list and turning them into camelCase as in DB
        $vulnerability_criteria_array = array_map('trim', explode(';', str_replace(' ', '', ucwords($vulnerability_criteria_string))));
        $formattedHouseholdArray['beneficiaries']['vulnerability_criteria'] = [];
        foreach ($vulnerability_criteria_array as $item) {
            $vulnerabilityId = $this->getVulnerabilityCriteriaId($item);
            if (empty($vulnerabilityId)) {
                continue;
            }
            $formattedHouseholdArray['beneficiaries']['vulnerability_criteria'][] = ['id' => $vulnerabilityId];
        }
    }

    /**
     * Returns the id of the vulnerability criteria passed in parameter
     *
     * @param string $name
     * @return int|null
     */
    private function getVulnerabilityCriteriaId(string $name)
    {
        if (empty($name)) {
            return null;
        }

        if (! array_key_exists($name, $this->vulnerabilityCriteriaIds)) {
            $repo          = $this->em->getRepository(VulnerabilityCriterion::class);
            $vulnerability = $repo->findOneByFieldString($name);

            $this->vulnerabilityCriteriaIds[$name] = $vulnerability ? $vulnerability->getId() : null;
        }

        return $this->vulnerabilityCriteriaIds[$name];
    }

    /**
     * Reformat the field phones => switch string 'type-number' to [type => type, number => number].
     *
     * @param $formattedHouseholdArray
     */
    private function mapPhones(&$formattedHouseholdArray)
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
     * Reformat the field gender
     *
     * @param $formattedHouseholdArray
     */
    private function mapGender(&$formattedHouseholdArray)
    {
        $gender_string = trim($formattedHouseholdArray['beneficiaries']['gender']);

        if (strcasecmp(trim($gender_string), 'Male') === 0 || strcasecmp(trim($gender_string), 'M') === 0) {
            $formattedHouseholdArray['beneficiaries']['gender'] = 1;
        } else if (strcasecmp(trim($gender_string), 'Female') === 0 || strcasecmp(trim($gender_string), 'F') === 0) {
            $formattedHouseholdArray['beneficiaries']['gender'] = 0;
        }
    }

    /**
     * Reformat the field nationalids => switch string 'idtype-idnumber' to [id_type => idtype, id_number => idnumber].
     *
     * @param $formattedHouseholdArray
     */
    private function mapNationalIds(&$formattedHouseholdArray)
    {
        $type_national_id = $formattedHouseholdArray['beneficiaries']['national_id_type'];
        $national_id_string = $formattedHouseholdArray['beneficiaries']['national_id_number'];
        $formattedHouseholdArray['beneficiaries']['national_ids'] = [];
        if ($national_id_string != '') {
            $formattedHouseholdArray['beneficiaries']['national_ids'][] = ['id_type' => $type_national_id, 'id_number' => $national_id_string];
        }
    }

    /**
     * Reformat the field profile.
     *
     * @param $formattedHouseholdArray
     */
    private function mapProfile(&$formattedHouseholdArray)
    {
        $formattedHouseholdArray['beneficiaries']['profile'] = ['photo' => ''];
    }

    /**
     * Reformat the field status.
     *
     * @param $formattedHouseholdArray
     */
    private function mapStatus(&$formattedHouseholdArray)
    {
        $formattedHouseholdArray['beneficiaries']['status'] =  $formattedHouseholdArray['beneficiaries']['status'] === 'false' ? 0 : 1;
    }

    /**
     * Makes sure the ADM are only retrieved once from the database to save database accesses
     *
     * @param mixed[] $location
     * @param string $admClass
     * @param string $admType
     * @param string $parentAdmType
     * @param mixed $parentAdm
     *
     * @return mixed
     */
    private function getAdmByLocation(&$location, string $admClass, string $admType, string $parentAdmType = null, &$parentAdm = null)
    {
        // The query schema is different for the Adm1
        if ($admClass === Adm1::class) {
            $query = [
                'name' => $location['adm1'],
                'countryISO3' => $location['country_iso3']
            ];
        }

        // Return the ADM if it has already been loaded before
        if (! empty($this->adms[$admType][$location[$admType]])) {
            return $this->adms[$admType][$location[$admType]];
        }

        // If it is not an Adm1, build the query
        if (empty($query)) {
            $query = ['name' => $location[$admType]];
            $query[$parentAdmType] = $parentAdm;
        }

        // Store the result of the query for next times
        $this->adms[$admType][$location[$admType]] = $this->em->getRepository($admClass)->findOneBy($query);

        return $this->adms[$admType][$location[$admType]];
    }

    /**
     * Reformat the field location.
     *
     * @param $formattedHouseholdArray
     * @throws \Exception
     */
    public function mapLocation(&$formattedHouseholdArray)
    {
        $location = $formattedHouseholdArray['location'];

        if ($location['adm1'] === null && $location['adm2'] === null && $location['adm3'] === null && $location['adm4'] === null) {
            if ($formattedHouseholdArray['address_street'] || $formattedHouseholdArray['camp']) {
                throw new \Exception('A location is required');
            } else {
                return;
            }
        }

        if (! $location['adm1']) {
            throw new \Exception('An Adm1 is required');
        }

        // Map adm1
        $adm1 = $this->getAdmByLocation($location, Adm1::class, 'adm1');

        if (! $adm1 instanceof Adm1) {
            throw new \Exception('The Adm1 ' . $location['adm1'] . ' was not found in ' . $location['country_iso3']);
        } else {
            $formattedHouseholdArray['location']['adm1'] = $adm1->getId();
        }

        if (! $location['adm2']) {
            return;
        }

        // Map adm2
        $adm2 = $this->getAdmByLocation($location, Adm2::class, 'adm2', 'adm1', $adm1);

        if (! $adm2 instanceof Adm2) {
            throw new \Exception('The Adm2 ' . $location['adm2'] . ' was not found in ' . $adm1->getName());
        } else {
            $formattedHouseholdArray['location']['adm2'] = $adm2->getId();
        }

        if (! $location['adm3']) {
            return;
        }

        // Map adm3
        $adm3 = $this->getAdmByLocation($location, Adm3::class, 'adm3', 'adm2', $adm2);

        if (! $adm3 instanceof Adm3) {
            throw new \Exception('The Adm3 ' . $location['adm3'] . ' was not found in ' . $adm2->getName());
        } else {
            $formattedHouseholdArray['location']['adm3'] = $adm3->getId();
        }

        if (! $location['adm4']) {
            return;
        }

        // Map adm4
        $adm4 = $this->getAdmByLocation($location, Adm4::class, 'adm4', 'adm3', $adm3);

        if (! $adm4 instanceof Adm4) {
            throw new \Exception('The Adm4 ' . $location['adm4'] . ' was not found in ' . $adm3->getName());
        } else {
            $formattedHouseholdArray['location']['adm4'] = $adm4->getId();
        }
    }

    /**
     * Reformat the field livelihood.
     *
     * @param $formattedHouseholdArray
     */
    public function mapLivelihood(&$formattedHouseholdArray)
    {
        if ($formattedHouseholdArray['livelihood']) {
            $livelihood = null;
            foreach (Household::LIVELIHOOD as $livelihoodId => $value) {
                if (strcasecmp($value, $formattedHouseholdArray['livelihood']) === 0) {
                    $livelihood = $livelihoodId;
                }
            }
            if ($livelihood !== null) {
                $formattedHouseholdArray['livelihood'] = $livelihood;
            } else {
                throw new \Exception("Invalid livelihood.");
            }
        }
    }
}
