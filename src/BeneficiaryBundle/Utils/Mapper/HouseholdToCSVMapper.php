<?php

namespace BeneficiaryBundle\Utils\Mapper;

use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Household;
use CommonBundle\Utils\LocationService;
use DistributionBundle\Entity\DistributionData;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HouseholdToCSVMapper extends AbstractMapper
{
    /** @var Serializer $serializer */
    private $serializer;

    /** @var LocationService $locationService */
    private $locationService;

    public function __construct(EntityManagerInterface $entityManager, Serializer $serializer, LocationService $locationService)
    {
        parent::__construct($entityManager);
        $this->serializer = $serializer;
        $this->locationService = $locationService;
    }

    /**
     * @param Worksheet $worksheet
     * @param array $receivers
     * @param $countryISO3
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function fromHouseholdToCSV(Worksheet $worksheet, array $receivers, $countryISO3)
    {
        $arraySheet = $worksheet->rangeToArray('A1:' . $worksheet->getHighestColumn() . $worksheet->getHighestRow(), null, true, true, true);
        $mapping = $this->loadMappingCSVOfCountry($countryISO3);
        $householdsArrayCSV = $arraySheet;
        $lastColumn = null;
        /** @var Household $receiver */
        foreach ($receivers as $receiver)
        {
            $householdArrayCSV = [];

            $householdArray = json_decode(
                $this->serializer
                    ->serialize(
                        $receiver,
                        'json',
                        SerializationContext::create()->setSerializeNull(true)->setGroups(["FullHousehold"])
                    ),
                true);

            foreach ($mapping as $fieldName => $columnCsv)
            {
                if (is_array($columnCsv))
                {
                    foreach ($columnCsv as $fieldName2 => $columnCsv2)
                    {
                        if (
                            array_key_exists($fieldName, $householdArray)
                            || is_array($householdArray[$fieldName])
                            || !array_key_exists($fieldName2, $householdArray[$fieldName])
                        )
                        {
                            if ("location" === $fieldName)
                            {
                                $householdArrayCSV[0][$columnCsv2] = $this->getLocationField($receiver, $fieldName2);
                            }
                            else
                            {
                                $householdArrayCSV[0][$columnCsv2] = null;
                            }
                        }
                        else
                        {
                            $householdArrayCSV[0][$columnCsv2] = $householdArray[$fieldName][$fieldName2];
                        }
                        $lastColumn = $columnCsv2;
                    }
                }
                else
                {
                    if (substr($fieldName, 0, 20) === "tmp_country_specific")
                    {
                        $householdArrayCSV[0][$columnCsv] = $this
                            ->getCountrySpecificAnswer($countryISO3, $receiver, $arraySheet, $columnCsv);
                    }
                    elseif (!array_key_exists($fieldName, $householdArray))
                    {
                        $householdArrayCSV[0][$columnCsv] = null;
                    }
                    else
                    {
                        $householdArrayCSV[0][$columnCsv] = $householdArray[$fieldName];
                    }
                    $lastColumn = $columnCsv;
                }
            }
            $lastColumn = $this->SUMOfLetter($lastColumn, 1);
            $householdArrayCSV[0][$lastColumn] = $receiver->getId();

            $this->fieldBeneficiary($householdArrayCSV, $householdArray, $mapping);
            $householdsArrayCSV = array_merge($householdsArrayCSV, $householdArrayCSV);
        }
        $householdsArrayCSV[1][$lastColumn] = DistributionData::NAME_HEADER_ID;
        $worksheet->fromArray($householdsArrayCSV, true, 'A1', true);
    }

    /**
     * Reformat the field beneficiary
     * @param array $householdArrayCSV
     * @param array $householdArray
     * @param $mapping
     */
    private function fieldBeneficiary(array &$householdArrayCSV, array $householdArray, $mapping)
    {
        foreach ($householdArray["beneficiaries"] as $index => $beneficiaryArray)
        {
            foreach ($mapping["beneficiaries"] as $fieldName => $columnCsv)
            {
                $this->setBeneficiaryNullFields($householdArrayCSV, $index, $mapping);
                if (array_key_exists($fieldName, $beneficiaryArray))
                {
                    switch ($fieldName)
                    {
                        case 'phones':
                            $this
                                ->fieldPhones($householdArrayCSV, $index, $columnCsv, $beneficiaryArray[$fieldName]);
                            break;
                        case 'national_ids':
                            $this
                                ->fieldNationalIds($householdArrayCSV, $index, $columnCsv, $beneficiaryArray[$fieldName]);
                            break;
                        case 'vulnerability_criteria':
                            $this
                                ->fieldVulnerabilityCriteria($householdArrayCSV, $index, $columnCsv, $beneficiaryArray[$fieldName]);
                            break;
                        default:
                            if (false === $beneficiaryArray[$fieldName])
                                $householdArrayCSV[$index][$columnCsv] = 0;
                            elseif (true === $beneficiaryArray[$fieldName])
                                $householdArrayCSV[$index][$columnCsv] = 1;
                            else
                                $householdArrayCSV[$index][$columnCsv] = $beneficiaryArray[$fieldName];
                            break;
                    }
                }
                else
                    $householdArrayCSV[$index][$columnCsv] = null;
            }
        }

    }

    /**
     * @param array $householdArrayCSV
     * @param $index
     * @param $mapping
     */
    private function setBeneficiaryNullFields(array &$householdArrayCSV, $index, $mapping)
    {
        if (0 === intval($index))
            return;

        foreach ($mapping as $fieldName => $columnCsv)
        {
            if (is_array($columnCsv))
            {
                if ("beneficiaries" === $fieldName)
                    continue;
                foreach ($columnCsv as $fieldName2 => $columnCsv2)
                {
                    $householdArrayCSV[$index][$columnCsv2] = null;
                }
            }
            else
            {
                $householdArrayCSV[$index][$columnCsv] = null;

            }
        }
    }

    /**
     * @param $countryISO3
     * @param Household $receiver
     * @param $arraySheet
     * @param $columnCsv
     * @return null|string
     */
    public function getCountrySpecificAnswer($countryISO3, Household $receiver, $arraySheet, $columnCsv)
    {
        $countrySpecific = $this->em->getRepository(CountrySpecific::class)
            ->findOneBy([
                "fieldString" => $arraySheet[Household::indexRowHeader][$columnCsv],
                "countryIso3" => $countryISO3
            ]);
        if (!$countrySpecific instanceof CountrySpecific)
            return null;
        $countrySpecificAnswer = $this->em->getRepository(CountrySpecificAnswer::class)
            ->findOneBy([
                "countrySpecific" => $countrySpecific,
                "household" => $receiver
            ]);
        if (!$countrySpecificAnswer instanceof CountrySpecificAnswer)
            return null;

        return $countrySpecificAnswer->getAnswer();
    }

    /**
     * Reformat the field which contains vulnerability criteria => switch list of names to a list of ids
     * @param $householdArrayCSV
     * @param $index
     * @param $columnCsv
     * @param $vulnerabilitiesCriteriaArray
     */
    private function fieldVulnerabilityCriteria(&$householdArrayCSV, $index, $columnCsv, $vulnerabilitiesCriteriaArray)
    {
        $vulnerabilitiesCriteriaString = "";
        foreach ($vulnerabilitiesCriteriaArray as $vulnerabilityCriteriaArray)
        {
            if ("" !== $vulnerabilitiesCriteriaString)
                $vulnerabilitiesCriteriaString .= " ; ";
            $vulnerabilitiesCriteriaString .= $vulnerabilityCriteriaArray["field_string"];
        }
        $householdArrayCSV[$index][$columnCsv] = $vulnerabilitiesCriteriaString;
    }

    /**
     * Reformat the field phones => switch string [type => type, number => number] to 'type-number'
     * @param $householdArrayCSV
     * @param $index
     * @param $columnCsv
     * @param $phonesArray
     */
    private function fieldPhones(&$householdArrayCSV, $index, $columnCsv, $phonesArray)
    {
        $phonesString = "";
        foreach ($phonesArray as $phoneArray)
        {
            if ("" !== $phonesString)
                $phonesString .= " ; ";
            $phonesString .= $phoneArray["type"] . " - " . $phoneArray["number"];
        }
        $householdArrayCSV[$index][$columnCsv] = $phonesString;
    }

    /**
     * Reformat the field nationalids => switch string [id_type => idtype, id_number => idnumber] to 'idtype-idnumber'
     * @param $householdArrayCSV
     * @param $index
     * @param $columnCsv
     * @param $nationalIdsArray
     */
    private function fieldNationalIds(&$householdArrayCSV, $index, $columnCsv, $nationalIdsArray)
    {
        $nationalIdsString = "";
        foreach ($nationalIdsArray as $nationalIdArray)
        {
            if ("" !== $nationalIdsString)
                $nationalIdsString .= " ; ";
            $nationalIdsString .= $nationalIdArray["id_type"] . " - " . $nationalIdArray["id_number"];
        }
        $householdArrayCSV[$index][$columnCsv] = $nationalIdsString;
    }

    /**
     * @param Household $receiver
     * @param $admField
     * @return mixed|null
     */
    public function getLocationField(Household $receiver, $admField)
    {
        $admField = ucfirst($admField);
        if (!is_callable([$this->locationService, "get" . $admField]))
        {
            return null;
        }
        $admString = call_user_func([$this->locationService, "get" . $admField], $receiver);
        return $admString;
    }

}
