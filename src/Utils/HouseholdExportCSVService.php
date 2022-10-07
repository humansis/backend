<?php

namespace Utils;

use Entity\CountrySpecific;
use Utils\ExportService;
use Doctrine\ORM\EntityManagerInterface;
use Component\Import\ImportTemplate;
use Enum\NationalIdType;

class HouseholdExportCSVService
{
    public const
        PRIMARY_ID_TYPE = 'ID Type',
        PRIMARY_ID_NUMBER = 'ID Number',
        SECONDARY_ID_TYPE = 'Secondary ID Type',
        SECONDARY_ID_NUMBER = 'Secondary ID Number',
        TERNARY_ID_TYPE = 'Ternary ID Type',
        TERNARY_ID_NUMBER = 'Ternary ID Number',
        HEAD = 'Head';

    /** @var EntityManagerInterface */
    private $em;

    /** @var ExportService */
    private $exportService;

    private const LINE_1_MAPPING = [
        ImportTemplate::ROW_NAME_STATUS => '(!) Do not remove lines 1-5',
        ImportTemplate::ROW_NAME_MESSAGES => '',
        'Address street' => '',
        'Address number' => '',
        'Address postcode' => 'Help',
        'Camp name' => '',
        'Tent number' => '',
        'Livelihood' => '',
        'Income' => 'EITHER ** address OR *** camp information is required',
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
        'Local given name' => '',
        'Local family name' => '',
        'Local parent\'s name' => '',
        'English given name' => '',
        'English family name' => '',
        'English parent\'s name' => '',
        'Gender' => '',
        self::HEAD => '',
        'Residency status' => '',
        'Date of birth' => '',
        'Vulnerability criteria' => '',
        'Type phone 1' => '',
        'Prefix phone 1' => '',
        'Number phone 1' => '',
        'Proxy phone 1' => '',
        'Type phone 2' => '',
        'Prefix phone 2' => '',
        'Number phone 2' => '',
        'Proxy phone 2' => '',
        self::PRIMARY_ID_TYPE => '',
        self::PRIMARY_ID_NUMBER => '',
        self::SECONDARY_ID_TYPE => '',
        self::SECONDARY_ID_NUMBER => '',
        self::TERNARY_ID_TYPE => '',
        self::TERNARY_ID_NUMBER => '',
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

    private const LINE_2_MAPPING = [
        ImportTemplate::ROW_NAME_STATUS => '(!) Do not remove columns A-B',
        ImportTemplate::ROW_NAME_MESSAGES => '',
        'Address street' => '',
        'Address number' => '',
        'Address postcode' => 'https://docs.humansis.org/x/kQBf',
        'Camp name' => '',
        'Tent number' => '',
        'Livelihood' => '',
        'Income' => '* required property',
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
        'Local given name' => '',
        'Local family name' => '',
        'Local parent\'s name' => '',
        'English given name' => '',
        'English family name' => '',
        'English parent\'s name' => '',
        'Gender' => '',
        self::HEAD => '',
        'Residency status' => '',
        'Date of birth' => '',
        'Vulnerability criteria' => '',
        'Type phone 1' => '',
        'Prefix phone 1' => '',
        'Number phone 1' => '',
        'Proxy phone 1' => '',
        'Type phone 2' => '',
        'Prefix phone 2' => '',
        'Number phone 2' => '',
        'Proxy phone 2' => '',
        self::PRIMARY_ID_TYPE => '',
        self::PRIMARY_ID_NUMBER => '',
        self::SECONDARY_ID_TYPE => '',
        self::SECONDARY_ID_NUMBER => '',
        self::TERNARY_ID_TYPE => '',
        self::TERNARY_ID_NUMBER => '',
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

    private const LINE_3_MAPPING = [
        ImportTemplate::ROW_NAME_STATUS => '',
        ImportTemplate::ROW_NAME_MESSAGES => '',
        'Address street' => '',
        'Address number' => '',
        'Address postcode' => '',
        'Camp name' => '',
        'Tent number' => '',
        'Livelihood' => '',
        'Income' => '',
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
        'Local given name' => '',
        'Local family name' => '',
        'Local parent\'s name' => '',
        'English given name' => '',
        'English family name' => '',
        'English parent\'s name' => '',
        'Gender' => '',
        self::HEAD => '',
        'Residency status' => '',
        'Date of birth' => '',
        'Vulnerability criteria' => '',
        'Type phone 1' => '',
        'Prefix phone 1' => '',
        'Number phone 1' => '',
        'Proxy phone 1' => '',
        'Type phone 2' => '',
        'Prefix phone 2' => '',
        'Number phone 2' => '',
        'Proxy phone 2' => '',
        self::PRIMARY_ID_TYPE => '',
        self::PRIMARY_ID_NUMBER => '',
        self::SECONDARY_ID_TYPE => '',
        self::SECONDARY_ID_NUMBER => '',
        self::TERNARY_ID_TYPE => '',
        self::TERNARY_ID_NUMBER => '',
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

    private const LINE_4_MAPPING = [
        ImportTemplate::ROW_NAME_STATUS => ImportTemplate::CURRENT_TEMPLATE_VERSION,
        ImportTemplate::ROW_NAME_MESSAGES => '',
        'Address street' => '**String',
        'Address number' => '**String',
        'Address postcode' => '**String',
        'Camp name' => '***String',
        'Tent number' => '***Number',
        'Livelihood' => 'String',
        'Income' => 'Number',
        'Food Consumption Score' => 'Number',
        'Coping Strategies Index' => 'Number',
        'Notes' => 'String',
        'Enumerator name' => 'String',
        'Latitude' => 'Float',
        'Longitude' => 'Float',
        'Adm1' => '*String/empty',
        'Adm2' => 'String/empty',
        'Adm3' => 'String/empty',
        'Adm4' => 'String/empty',
        'Local given name' => '*String',
        'Local family name' => '*String',
        'Local parent\'s name' => 'String',
        'English given name' => 'String',
        'English family name' => 'String',
        'English parent\'s name' => 'String',
        'Gender' => '*Male / Female',
        self::HEAD => '*String [true-false]',
        'Residency status' => '*Refugee / IDP / Resident / Returnee',
        'Date of birth' => '*DD-MM-YYYY',
        'Vulnerability criteria' => 'String',
        'Type phone 1' => 'Mobile / Landline',
        'Prefix phone 1' => "'+X",
        'Number phone 1' => 'Number',
        'Proxy phone 1' => 'Y / N (Proxy)',
        'Type phone 2' => 'Mobile / Landline',
        'Prefix phone 2' => "'+X",
        'Number phone 2' => 'Number',
        'Proxy phone 2' => 'Y / N (Proxy)',
        self::PRIMARY_ID_TYPE => 'String',
        self::PRIMARY_ID_NUMBER => 'String',
        self::SECONDARY_ID_TYPE => 'String',
        self::SECONDARY_ID_NUMBER => 'String',
        self::TERNARY_ID_TYPE => 'String',
        self::TERNARY_ID_NUMBER => 'String',
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

    public const MAPPING_PROPERTIES = [
        ImportTemplate::ROW_NAME_STATUS => 'humansisData',
        ImportTemplate::ROW_NAME_MESSAGES => 'humansisComment',
        'Address street' => 'addressStreet',
        'Address number' => 'addressNumber',
        'Address postcode' => 'addressPostcode',
        'Camp name' => 'campName',
        'Tent number' => 'tentNumber',
        'Livelihood' => 'livelihood',
        'Income' => 'income',
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
        self::HEAD => 'head',
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
        self::PRIMARY_ID_TYPE => 'primaryIdType',
        self::PRIMARY_ID_NUMBER => 'primaryIdNumber',
        self::SECONDARY_ID_TYPE => 'secondaryIdType',
        self::SECONDARY_ID_NUMBER => 'secondaryIdNumber',
        self::TERNARY_ID_TYPE => 'tertiaryIdType',
        self::TERNARY_ID_NUMBER => 'tertiaryIdNumber',
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

    public function __construct(EntityManagerInterface $entityManager, ExportService $exportService)
    {
        $this->em = $entityManager;
        $this->exportService = $exportService;
    }

    /**
     * @param $countryIso3
     *
     * @return mixed
     */
    private function getCountrySpecifics($countryIso3)
    {
        return $this->em->getRepository(CountrySpecific::class)
            ->findBy(['countryIso3' => $countryIso3], ['id' => 'asc']);
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
        $first = true;
        foreach ($this->getCountrySpecifics($countryISO3) as $countrySpecific) {
            $countryField = $countrySpecific->getFieldString();

            $specificHxl[$countryField] = '';
            $specificHouseholdHead[$countryField] = '';
            $specificDependent[$countryField] = $first ? 'Country specific options' : '';
            $specificDetails[$countryField] = $countrySpecific->getType();
            $first = false;
        }

        return [
            array_merge(self::LINE_1_MAPPING, $specificHxl),
            array_merge(self::LINE_2_MAPPING, $specificHouseholdHead),
            array_merge(self::LINE_3_MAPPING, $specificDependent),
            array_merge(self::LINE_4_MAPPING, $specificDetails),
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
        return $this->exportService->export(
            $this->getHeaders($countryISO3),
            'pattern_household_' . $countryISO3,
            $type,
            true
        );
    }
}
