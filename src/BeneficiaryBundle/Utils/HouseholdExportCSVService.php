<?php

namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\CountrySpecific;
use CommonBundle\Utils\ExportService;
use Doctrine\ORM\EntityManagerInterface;

class HouseholdExportCSVService
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var ExportService */
    private $exportService;

    public const MAPPING_PROPERTIES = [
        'Address street' => 'addressStreet',
        'Address number' => 'addressNumber',
        'Address postcode' => 'addressPostcode',
        'Camp name' => 'campName',
        'Tent number' => 'tentNumber',
        'Livelihood' => 'livelihood',
        'Income level' => 'incomeLevel',
        'Food Consumption Score' => 'foodConsumptionScore',
        'Coping Strategies Index' => 'copingStrategiesIndex',
        'Notes' => 'notes',
        'Enumerator name' => 'enumeratorName',
        'Latitude' => 'latitude',
        'Longitude' => 'longitude',
        'Adm1' => 'adm1',
        'Adm2' => 'adm2',
        'Adm3' => 'adm3',
        'Adm4' => 'adm4',
        'Local given name' => 'localGivenName',
        'Local family name' => 'localFamilyName',
        'Local parent\'s name' => 'localParentsName',
        'English given name' => 'englishGivenName',
        'English family name' => 'englishFamilyName',
        'English parent\'s name' => 'englishParentsName',
        'Gender' => 'gender',
        'Head' => 'head',
        'Residency status' => 'residencyStatus',
        'Date of birth' => 'dateOfBirth',
        'Vulnerability criteria' => 'vulnerabilityCriteria',
        'Type phone 1' => 'typePhone1',
        'Prefix phone 1' => 'prefixPhone1',
        'Number phone 1' => 'numberPhone1',
        'Proxy phone 1' => 'proxyPhone1',
        'Type phone 2' => 'typePhone2',
        'Prefix phone 2' => 'prefixPhone2',
        'Number phone 2' => 'numberPhone2',
        'Proxy phone 2' => 'proxyPhone2',
        'ID Type' => 'idType',
        'ID Number' => 'idNumber',
        'Shelter status' => 'shelterStatus',
        'Assets' => 'assets',
        'Debt Level' => 'debtLevel',
        'Support Received Types' => 'supportReceivedTypes',
        'Support Date Received' => 'supportDateReceived',
        'F 0 - 2' => 'f0',
        'F 2 - 5' => 'f2',
        'F 6 - 17' => 'f6',
        'F 18 - 59' => 'f18',
        'F 60+' => 'f60',
        'M 0 - 2' => 'm0',
        'M 2 - 5' => 'm2',
        'M 6 - 17' => 'm6',
        'M 18 - 59' => 'm18',
        'M 60+' => 'm60',
    ];
    
    private const MAPPING_HXL = [
        'Address street' => '#contact+address_street',
        'Address number' => '#contact+address_number',
        'Address postcode' => '#contact+address_postcode',
        'Camp name' => '',
        'Tent number' => '',
        'Livelihood' => '',
        'Income level' => '',
        'Food Consumption Score' => '',
        'Coping Strategies Index' => '',
        'Notes' => '#description+notes',
        'Enumerator name' => '',
        'Latitude' => '#geo+lat',
        'Longitude' => '#geo+lon',
        'Adm1' => '#adm1+name',
        'Adm2' => '#adm2+name',
        'Adm3' => '#adm3+name',
        'Adm4' => '#adm4+name',
        'Local given name' => '#beneficiary+localGivenName',
        'Local family name' => '#beneficiary+localFamilyName',
        'Local parent\'s name' => '',
        'English given name' => '#beneficiary+enGivenName',
        'English family name' => '#beneficiary+enFamilyName',
        'English parent\'s name' => '',
        'Gender' => '',
        'Head' => '',
        'Residency status' => '',
        'Date of birth' => '#beneficiary+birth',
        'Vulnerability criteria' => '',
        'Type phone 1' => '',
        'Prefix phone 1' => '',
        'Number phone 1' => '#contact+phone',
        'Proxy phone 1' => '',
        'Type phone 2' => '',
        'Prefix phone 2' => '',
        'Number phone 2' => '#contact+phone',
        'Proxy phone 2' => '',
        'ID Type' => '',
        'ID Number' => '',
        'Shelter status' => '',
        'Assets' => '',
        'Debt Level' => '',
        'Support Received Types' => '',
        'Support Date Received' => '',
        'F 0 - 2' => '',
        'F 2 - 5' => '',
        'F 6 - 17' => '',
        'F 18 - 59' => '',
        'F 60+' => '',
        'M 0 - 2' => '',
        'M 2 - 5' => '',
        'M 6 - 17' => '',
        'M 18 - 59' => '',
        'M 60+' => '',
    ];

    private const MAPPING_HOUSEHOLD_HEAD = [
        'Address street' => 'Thompson Drive',
        'Address number' => '4943',
        'Address postcode' => '94801',
        'Camp name' => 'Some Camp',
        'Tent number' => '10',
        'Livelihood' => 'Education',
        'Income level' => '3',
        'Food Consumption Score' => '3',
        'Coping Strategies Index' => '2',
        'Notes' => 'Greatest city',
        'Enumerator name' => 'John Smith',
        'Latitude' => '38.018234',
        'Longitude' => '-122.379730',
        'Adm1' => 'USA',
        'Adm2' => 'California',
        'Adm3' => 'CA',
        'Adm4' => 'Richmond',
        'Local given name' => 'Price',
        'Local family name' => 'Smith',
        'Local parent\'s name' => '',
        'English given name' => 'Price',
        'English family name' => 'Smith',
        'English parent\'s name' => '',
        'Gender' => 'Female',
        'Head' => 'true',
        'Residency status' => 'Refugee',
        'Date of birth' => '31-10-1990',
        'Vulnerability criteria' => 'disabled',
        'Type phone 1' => 'Mobile',
        'Prefix phone 1' => "'+855",
        'Number phone 1' => "'145678348",
        'Proxy phone 1' => 'N',
        'Type phone 2' => 'Landline',
        'Prefix phone 2' => "'+855",
        'Number phone 2' => "'223543767",
        'Proxy phone 2' => 'N',
        'ID Type' => 'National ID',
        'ID Number' => '030617701',
        'Shelter status' => '',
        'Assets' => '',
        'Debt Level' => '',
        'Support Received Types' => '',
        'Support Date Received' => '',
        'F 0 - 2' => 2,
        'F 2 - 5' => 0,
        'F 6 - 17' => 0,
        'F 18 - 59' => 0,
        'F 60+' => 1,
        'M 0 - 2' => 0,
        'M 2 - 5' => 3,
        'M 6 - 17' => 0,
        'M 18 - 59' => 0,
        'M 60+' => 0,
    ];

    private const MAPPING_HOUSEHOLD_MEMBER = [
        'Address street' => '',
        'Address number' => '',
        'Address postcode' => '',
        'Camp name' => '',
        'Tent number' => '',
        'Livelihood' => '',
        'Income level' => '',
        'Food Consumption Score' => '',
        'Coping Strategies Index' => '',
        'Notes' => '',
        'Enumerator name' => '',
        'Latitude' => '',
        'Longitude' => '',
        'Adm1' => '',
        'Adm2' => '',
        'Adm3' => '',
        'Adm4' => '',
        'Local given name' => 'James',
        'Local family name' => 'Smith',
        'Local parent\'s name' => '',
        'English given name' => 'James',
        'English family name' => 'Smith',
        'English parent\'s name' => '',
        'Gender' => 'Male',
        'Head' => 'false',
        'Residency status' => 'Resident',
        'Date of birth' => '25-07-2001',
        'Vulnerability criteria' => '',
        'Type phone 1' => 'Mobile',
        'Prefix phone 1' => "'+855",
        'Number phone 1' => "'145678323",
        'Proxy phone 1' => 'Y',
        'Type phone 2' => 'Landline',
        'Prefix phone 2' => "'+855",
        'Number phone 2' => "'265348764",
        'Proxy phone 2' => 'Y',
        'ID Type' => '',
        'ID Number' => '',
        'Shelter status' => '',
        'Assets' => '',
        'Debt Level' => '',
        'Support Received Types' => '',
        'Support Date Received' => '',
        'F 0 - 2' => '',
        'F 2 - 5' => '',
        'F 6 - 17' => '',
        'F 18 - 59' => '',
        'F 60+' => '',
        'M 0 - 2' => '',
        'M 2 - 5' => '',
        'M 6 - 17' => '',
        'M 18 - 59' => '',
        'M 60+' => '',
    ];

    private const MAPPING_DETAILS = [
        'Address street' => 'String*',
        'Address number' => 'Number*',
        'Address postcode' => 'Number*',
        'Camp name' => 'String*',
        'Tent number' => 'Number*',
        'Livelihood' => 'String',
        'Income level' => 'Number [1-5]',
        'Food Consumption Score' => 'Number',
        'Coping Strategies Index' => 'Number',
        'Notes' => 'String',
        'Enumerator name' => 'String',
        'Latitude' => 'Float',
        'Longitude' => 'Float',
        'Adm1' => 'String/empty',
        'Adm2' => 'String/empty',
        'Adm3' => 'String/empty',
        'Adm4' => 'String/empty',
        'Local given name' => 'String*',
        'Local family name' => 'String*',
        'Local parent\'s name' => 'String',
        'English given name' => 'String',
        'English family name' => 'String',
        'English parent\'s name' => 'String',
        'Gender' => 'Male / Female*',
        'Head' => 'String [true-false]*',
        'Residency status' => 'Refugee / IDP / Resident / Returnee*',
        'Date of birth' => 'DD-MM-YYYY',
        'Vulnerability criteria' => 'String',
        'Type phone 1' => 'Mobile / Landline',
        'Prefix phone 1' => "'+X",
        'Number phone 1' => 'Number',
        'Proxy phone 1' => 'Y / N (Proxy)',
        'Type phone 2' => 'Mobile / Landline',
        'Prefix phone 2' => "'+X",
        'Number phone 2' => 'Number',
        'Proxy phone 2' => 'Y / N (Proxy)',
        'ID Type' => '"TypeAsString"',
        'ID Number' => 'Number',
        'Shelter status' => 'String',
        'Assets' => 'Comma separated strings',
        'Debt Level' => 'Number',
        'Support Received Types' => 'Comma separated strings',
        'Support Date Received' => 'DD-MM-YYYY',
        'F 0 - 2' => 'Number',
        'F 2 - 5' => 'Number',
        'F 6 - 17' => 'Number',
        'F 18 - 59' => 'Number',
        'F 60+' => 'Number',
        'M 0 - 2' => 'Number',
        'M 2 - 5' => 'Number',
        'M 6 - 17' => 'Number',
        'M 18 - 59' => 'Number',
        'M 60+' => 'Number',
    ];

    private const MAPPING_HELP_HXL = [
        '  ' => '',
        '' => '     -->',
        ' ' => 'Do not remove this line.',
    ];

    private const MAPPING_HELP_HOUSEHOLD_HEAD = [
        '  ' => '[Head]',
        '' => '     -->',
        ' ' => 'This Example line and the Type Helper line below must not be removed.',
    ];

    private const MAPPING_HELP_HOUSEHOLD_MEMBER = [
        '  ' => '[Member]',
        '' => '     -->',
        ' ' => "'*' means that the property is needed -- An adm must be filled among Adm1/Adm2/Adm3/Adm4.",
    ];

    private const MAPPING_HELP_DETAILS = [
        '  ' => '',
        '' => '     -->',
        ' ' => "'*' means that the property is needed -- An adm must be filled among Adm1/Adm2/Adm3/Adm4.",
    ];

    public function __construct(EntityManagerInterface $entityManager, ExportService $exportService)
    {
        $this->em = $entityManager;
        $this->exportService = $exportService;
    }

    /**
     * @param $countryISO3
     *
     * @return mixed
     */
    private function getCountrySpecifics($countryISO3)
    {
        return $this->em->getRepository(CountrySpecific::class)->findByCountryIso3($countryISO3);
    }

    /**
     * Returns list headers cells.
     *
     * @param string $countryISO3
     *
     * @return array
     */
    public function getHeaders(string $countryISO3)
    {
        $specificHxl = $specificHouseholdHead = $specificDependent = $specificDetails = [];
        foreach ($this->getCountrySpecifics($countryISO3) as $countrySpecific) {
            $countryField = $countrySpecific->getFieldString();

            $specificHxl[$countryField] = '';
            $specificHouseholdHead[$countryField] = rand(0, 100);
            $specificDependent[$countryField] = '';
            $specificDetails[$countryField] = $countrySpecific->getType();
        }

        return [
            array_merge(self::MAPPING_HXL, $specificHxl, self::MAPPING_HELP_HXL),
            array_merge(self::MAPPING_HOUSEHOLD_HEAD, $specificHouseholdHead, self::MAPPING_HELP_HOUSEHOLD_HEAD),
            array_merge(self::MAPPING_HOUSEHOLD_MEMBER, $specificDependent, self::MAPPING_HELP_HOUSEHOLD_MEMBER),
            array_merge(self::MAPPING_DETAILS, $specificDetails, self::MAPPING_HELP_DETAILS),
        ];
    }

    /**
     * Export all projects of the country in the CSV file.
     *
     * @param string $type
     * @param string $countryISO3
     *
     * @return mixed
     */
    public function exportToCsv(string $type, string $countryISO3)
    {
        return $this->exportService->export($this->getHeaders($countryISO3), 'pattern_household_'.$countryISO3, $type);
    }
}
