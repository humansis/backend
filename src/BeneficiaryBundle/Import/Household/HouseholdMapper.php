<?php

namespace BeneficiaryBundle\Import\Household;

class HouseholdMapper
{
    /**
     * Mapping between fields and CSV columns.
     */
    private const DEFAULT_CONFIGURATION = [
        // Household
        'address_street' => 'A',
        'address_number' => 'B',
        'address_postcode' => 'C',
        'camp' => 'D',
        'tent_number' => 'E',
        'livelihood' => 'F',
        'income_level' => 'G',
        'food_consumption_score' => 'H',
        'coping_strategies_index' => 'I',
        'notes' => 'J',
        'latitude' => 'K',
        'longitude' => 'L',
        'location' => [
            // Location
            'adm1' => 'M',
            'adm2' => 'N',
            'adm3' => 'O',
            'adm4' => 'P',
        ],
        // Beneficiary
        'beneficiaries' => [
            'local_given_name' => 'Q',
            'local_family_name' => 'R',
            'en_given_name' => 'S',
            'en_family_name' => 'T',
            'gender' => 'U',
            'status' => 'V',
            'residency_status' => 'W',
            'date_of_birth' => 'X',
            'vulnerability_criteria' => 'Y',
            'phone1_type' => 'Z',
            'phone1_prefix' => 'AA',
            'phone1_number' => 'AB',
            'phone1_proxy' => 'AC',
            'phone2_type' => 'AD',
            'phone2_prefix' => 'AE',
            'phone2_number' => 'AF',
            'phone2_proxy' => 'AG',
            'national_id_type' => 'AH',
            'national_id_number' => 'AI',
        ],
        'member_f-0-2' => 'AJ',
        'member_f-2-5' => 'AK',
        'member_f-6-17' => 'AL',
        'member_f-18-64' => 'AM',
        'member_f-65-99' => 'AN',
        'member_m-0-2' => 'AO',
        'member_m-2-5' => 'AP',
        'member_m-6-17' => 'AQ',
        'member_m-18-64' => 'AR',
        'member_m-65-99' => 'AS',
        'shelter_status' => 'AT',
        'assets' => 'AU',
        'dept_level' => 'AV',
        'support_received_types' => 'AW',
        'support_date_received' => 'AX',
    ];

    /** @var array */
    private $configuration;

    public function __construct(string $countryISO, array $configuration = self::DEFAULT_CONFIGURATION)
    {
        $this->configuration = $configuration;
    }

    public function map($row)
    {
        // household_locations must exists
        // beneficiaries must exists
        // beneniciaries must have local_given_name, local_family_name, gender, status, residency_status, date_of_birth
    }
}
