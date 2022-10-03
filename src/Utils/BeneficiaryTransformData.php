<?php declare(strict_types=1);

namespace Utils;

use Entity\HouseholdLocation;
use Enum\EnumApiValueNoFoundException;
use Enum\HouseholdAssets;
use Enum\HouseholdShelterStatus;
use Enum\HouseholdSupportReceivedType;
use Enum\Livelihood;
use Enum\PersonGender;

class BeneficiaryTransformData
{

    /**
     * Returns an array representation of beneficiaries in order to prepare the export
     *
     * @param $beneficiaries
     *
     * @return array
     * @throws EnumApiValueNoFoundException
     */
    public function transformData($beneficiaries): array
    {
        $exportableTable = [];

        foreach ($beneficiaries as $beneficiary) {
            // Recover the phones of the beneficiary
            $typephones = ["", ""];
            $prefixphones = ["", ""];
            $valuesphones = ["", ""];
            $proxyphones = ["", ""];

            $index = 0;
            foreach ($beneficiary->getPhones()->getValues() as $value) {
                $typephones[$index] = $value->getType();
                $prefixphones[$index] = $value->getPrefix();
                $valuesphones[$index] = $value->getNumber();
                $proxyphones[$index] = $value->getProxy();
                $index++;
            }

            // Recover the  criterions from Vulnerability criteria object
            $valuescriteria = [];
            foreach ($beneficiary->getVulnerabilityCriteria()->getValues() as $value) {
                $valuescriteria[] = $value->getFieldString();
            }
            $valuescriteria = join(',', $valuescriteria);

            // Recover nationalID from nationalID object
            $typenationalID = [];
            $valuesnationalID = [];
            foreach ($beneficiary->getNationalIds()->getValues() as $value) {
                $typenationalID[] = $value->getIdType();
                $valuesnationalID[] = $value->getIdNumber();
            }
            $typenationalID = join(',', $typenationalID);
            $valuesnationalID = join(',', $valuesnationalID);

            //Recover country specifics for the household
            $valueCountrySpecific = [];
            foreach ($beneficiary->getHousehold()->getCountrySpecificAnswers()->getValues() as $value) {
                $valueCountrySpecific[$value->getCountrySpecific()->getFieldString()] = $value->getAnswer();
            }

            if ($beneficiary->getGender() == PersonGender::FEMALE) {
                $valueGender = "Female";
            } else {
                $valueGender = "Male";
            }

            $householdLocations = $beneficiary->getHousehold()->getHouseholdLocations();
            $currentHouseholdLocation = null;
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

            if ($beneficiary->getStatus() === true) {
                $finalArray = array_merge(
                    ["household ID" => $beneficiary->getHousehold()->getId()],
                    $householdFields,
                    ["adm1" => $adm1,
                        "adm2" => $adm2,
                        "adm3" => $adm3,
                        "adm4" => $adm4]
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
                    "latitude" => "",
                    "longitude" => "",
                    "adm1" => "",
                    "adm2" => "",
                    "adm3" => "",
                    "adm4" => "",
                ];
            }

            $assets = [];
            foreach ((array) $beneficiary->getHousehold()->getAssets() as $type) {
                $assets[] = HouseholdAssets::valueToAPI($type);
            }

            $supportReceivedTypes = [];
            foreach ((array) $beneficiary->getHousehold()->getSupportReceivedTypes() as $type) {
                $supportReceivedTypes[] = HouseholdSupportReceivedType::valueToAPI($type);
            }

            $shelterStatus = '';
            if ($beneficiary->getHousehold()->getShelterStatus()) {
                $shelterStatus = $beneficiary->getHousehold()->getShelterStatus() ? $beneficiary->getHousehold()->getShelterStatus() : '';
            }

            $tempBenef = [
                "beneficiary ID" => $beneficiary->getId(),
                "localGivenName" => $beneficiary->getLocalGivenName(),
                "localFamilyName" => $beneficiary->getLocalFamilyName(),
                "enGivenName" => $beneficiary->getEnGivenName(),
                "enFamilyName" => $beneficiary->getEnFamilyName(),
                "gender" => $valueGender,
                "head" => $beneficiary->isHead() ? "true" : "false",
                "residencyStatus" => $beneficiary->getResidencyStatus(),
                "dateOfBirth" => $beneficiary->getDateOfBirth(),
                "vulnerabilityCriteria" => $valuescriteria,
                "type phone 1" => $typephones[0],
                "prefix phone 1" => $prefixphones[0],
                "phone 1" => $valuesphones[0],
                "proxy phone 1" => $proxyphones[0],
                "type phone 2" => $typephones[1],
                "prefix phone 2" => $prefixphones[1],
                "phone 2" => $valuesphones[1],
                "proxy phone 2" => $proxyphones[1],
                "ID Type" => $typenationalID,
                "ID Number" => $valuesnationalID,
                "Assets" => implode(', ', $assets),
                "Shelter Status" => $shelterStatus,
                "Debt Level" => $beneficiary->getHousehold()->getDebtLevel(),
                "Support Received Types" => implode(', ', $supportReceivedTypes),
                "Support Date Received" => $beneficiary->getHousehold()->getSupportDateReceived() ? $beneficiary->getHousehold()->getSupportDateReceived()->format('d-m-Y') : null,
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
