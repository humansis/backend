<?php

declare(strict_types=1);

namespace Utils;

use Entity\HouseholdLocation;
use Enum\PersonGender;
use Repository\CountrySpecificRepository;

class BeneficiaryTransformData
{
    public function __construct(readonly private CountrySpecificRepository $countrySpecificRepository)
    {
    }

    /**
     * Returns an array representation of beneficiaries in order to prepare the export
     *
     * @param $beneficiaries
     * @param $country
     *
     * @return array
     */
    public function transformData($beneficiaries, $country): array
    {
        $exportableTable = [];

        $cso = $this->countrySpecificRepository->createQueryBuilder('db')
            ->select('db.fieldString as cso')
            ->Where('db.countryIso3 = :country')
            ->setParameter('country', $country)
            ->distinct('cso')
            ->getQuery()
            ->getResult();

        foreach ($beneficiaries as $beneficiary) {
            // Recover the phones of the beneficiary
            $phoneTypes = ["", ""];
            $phonePrefix = ["", ""];
            $phoneValues = ["", ""];
            $phoneProxies = ["", ""];

            $valueCountrySpecific = [];
            foreach ($cso as $value) {
                $valueCountrySpecific[$value['cso']] = "";
            }

            $index = 0;
            foreach ($beneficiary->getPerson()->getPhones()->getValues() as $value) {
                $phoneTypes[$index] = $value->getType();
                $phonePrefix[$index] = $value->getPrefix();
                $phoneValues[$index] = $value->getNumber();
                $phoneProxies[$index] = $value->getProxy();
                $index++;
            }

            // Recover the  criterions from Vulnerability criteria object
            $valuesCriteria = [];
            foreach ($beneficiary->getVulnerabilityCriteria() as $value) {
                $valuesCriteria[] = $value;
            }
            $valuesCriteria = join(',', $valuesCriteria);

            $primaryDocument = $beneficiary->getPerson()->getPrimaryNationalId();
            $secondaryDocument = $beneficiary->getPerson()->getSecondaryNationalId();
            $tertiaryDocument = $beneficiary->getPerson()->getTertiaryNationalId();


            foreach ($beneficiary->getHousehold()->getCountrySpecificAnswers()->getValues() as $value) {
                $valueCountrySpecific[$value->getCountrySpecific()->getFieldString()] = $value->getAnswer();
            }

            if ($beneficiary->getPerson()->getGender() == PersonGender::FEMALE) {
                $valueGender = "Female";
            } else {
                $valueGender = "Male";
            }

            $householdLocations = $beneficiary->getHousehold()->getHouseholdLocations();

            $currentHouseholdLocation = null;

            /** @var HouseholdLocation $householdLocation */
            foreach ($householdLocations as $householdLocation) {
                if ($householdLocation->getLocationGroup() === HouseholdLocation::LOCATION_GROUP_CURRENT) {
                    $currentHouseholdLocation = $householdLocation;
                }
            }

            $location = $currentHouseholdLocation->getLocation();

            $adm1 = $location->getAdm1Name();
            $adm2 = $location->getAdm2Name();
            $adm3 = $location->getAdm3Name();
            $adm4 = $location->getAdm4Name();

            $householdFields = $beneficiary->getCommonHouseholdExportFields();

            if ($beneficiary->isHead()) {
                $finalArray = array_merge(
                    ["household ID" => $beneficiary->getHousehold()->getId()],
                    $householdFields,
                    [
                        "adm1" => $adm1,
                        "adm2" => $adm2,
                        "adm3" => $adm3,
                        "adm4" => $adm4,
                    ]
                );
            } else {
                $finalArray = [
                    "household ID" => "",
                    "addressStreet" => "",
                    "addressNumber" => "",
                    "addressPostcode" => "",
                    "camp" => "",
                    "tent number" => "",
                    "livelihood" => "",
                    "incomeLevel" => "",
                    "foodConsumptionScore" => "",
                    "copingStrategiesIndex" => "",
                    "notes" => "",
                    "Enumerator name" => "",
                    "latitude" => "",
                    "longitude" => "",
                    "Assets" => "",
                    "Shelter Status" => "",
                    "Debt Level" => "",
                    "Support Received Types" => "",
                    "Support Date Received" => "",
                    "adm1" => "",
                    "adm2" => "",
                    "adm3" => "",
                    "adm4" => "",
                ];
            }

            $shelterStatus = '';
            if ($beneficiary->getHousehold()->getShelterStatus()) {
                $shelterStatus = $beneficiary->getHousehold()->getShelterStatus() ?: '';
            }
            $date = "";
            if ($beneficiary->getPerson()->getDateOfBirth()) {
                $date = date_format($beneficiary->getPerson()->getDateOfBirth(), "m/d/Y");
            }
            $tempBenef = [
                "beneficiary ID" => $beneficiary->getId(),
                "localGivenName" => $beneficiary->getPerson()->getLocalGivenName(),
                "localFamilyName" => $beneficiary->getPerson()->getLocalFamilyName(),
                "enGivenName" => $beneficiary->getPerson()->getEnGivenName(),
                "enFamilyName" => $beneficiary->getPerson()->getEnFamilyName(),
                "gender" => $valueGender,
                "head" => $beneficiary->isHead() ? "true" : "false",
                "residencyStatus" => $beneficiary->getResidencyStatus(),
                "dateOfBirth" => $date,
                "vulnerabilityCriteria" => $valuesCriteria,
                "type phone 1" => $phoneTypes[0],
                "prefix phone 1" => $phonePrefix[0],
                "phone 1" => $phoneValues[0],
                "proxy phone 1" => $phoneProxies[0],
                "type phone 2" => $phoneTypes[1],
                "prefix phone 2" => $phonePrefix[1],
                "phone 2" => $phoneValues[1],
                "proxy phone 2" => $phoneProxies[1],
                "primary ID type" => $primaryDocument ? $primaryDocument->getIdType() : '',
                "primary ID number" => $primaryDocument ? $primaryDocument->getIdNumber() : '',
                "secondary ID type" => $secondaryDocument ? $secondaryDocument->getIdType() : '',
                "secondary ID number" => $secondaryDocument ? $secondaryDocument->getIdNumber() : '',
                "tertiary ID type" => $tertiaryDocument ? $tertiaryDocument->getIdType() : '',
                "tertiary ID number" => $tertiaryDocument ? $tertiaryDocument->getIdNumber() : '',
                "Assets" => implode(', ', $beneficiary->getHousehold()->getAssets()),
                "Shelter Status" => $shelterStatus,
                "Debt Level" => $beneficiary->getHousehold()->getDebtLevel(),
                "Support Received Types" => implode(', ', $beneficiary->getHousehold()->getSupportReceivedTypes()),
                "Support Date Received" => $beneficiary->getHousehold()->getSupportDateReceived()
                    ? $beneficiary->getHousehold()->getSupportDateReceived()->format('d-m-Y')
                    : null,
            ];

            foreach ($valueCountrySpecific as $key => $value) {
                $finalArray[$key] = $value;
            }

            foreach ($tempBenef as $key => $value) {
                $finalArray[$key] = $value;
            }
            $exportableTable[] = $finalArray;
        }

        return $exportableTable;
    }
}
