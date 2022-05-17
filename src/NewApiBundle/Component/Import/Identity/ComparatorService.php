<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Identity;

use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\HouseholdLocation;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Repository\CountrySpecificRepository;
use CommonBundle\Entity\Location;
use CommonBundle\Repository\LocationRepository;
use NewApiBundle\Component\Import\Domain\Beneficiary;
use NewApiBundle\Component\Import\Domain\Household;
use NewApiBundle\Component\Import\Finishing\HouseholdDecoratorBuilder;
use NewApiBundle\Component\Import\Utils\CompareTrait;
use NewApiBundle\Component\Import\ValueObject\BeneficiaryCompare;
use NewApiBundle\Component\Import\ValueObject\HouseholdCompare;
use NewApiBundle\Component\Import\ValueObject\ScalarCompare;
use NewApiBundle\Enum\PersonGender;
use NewApiBundle\InputType\HouseholdCreateInputType;

class ComparatorService
{
    use CompareTrait;

    /** @var HouseholdDecoratorBuilder */
    private $decoratorBuilder;

    /** @var HouseholdCompare */
    private $object;

    /** @var LocationRepository */
    private $locationRepository;

    /** @var CountrySpecificRepository */
    private $countrySpecificsRepository;

    /** @var HouseholdCreateInputType */
    private $importingHousehold;

    /**
     * @param HouseholdDecoratorBuilder $decoratorBuilder
     * @param LocationRepository        $locationRepository
     * @param CountrySpecificRepository $countrySpecificsRepository
     */
    public function __construct(
        HouseholdDecoratorBuilder $decoratorBuilder,
        LocationRepository        $locationRepository,
        CountrySpecificRepository $countrySpecificsRepository
    ) {
        $this->decoratorBuilder = $decoratorBuilder;
        $this->locationRepository = $locationRepository;
        $this->countrySpecificsRepository = $countrySpecificsRepository;
    }

    public function compareHouseholds(Household $importedHousehold, \BeneficiaryBundle\Entity\Household $storedHousehold): HouseholdCompare
    {
        $importedHouseholdData = $importedHousehold->getInputType();
        $comparison = new HouseholdCompare();
        $comparison->setLivelihood($this->compareScalarValue($storedHousehold->getLivelihood(), $importedHouseholdData->getLivelihood()));
        $comparison->setShelterStatus($this->compareScalarValue($storedHousehold->getShelterStatus(), $importedHouseholdData->getShelterStatus()));
        $comparison->setNotes($this->compareScalarValue($storedHousehold->getNotes(), $importedHouseholdData->getNotes()));
        $comparison->setLatitude($this->compareScalarValue($storedHousehold->getLatitude(), $importedHouseholdData->getLatitude()));
        $comparison->setLatitude($this->compareScalarValue($storedHousehold->getLatitude(), $importedHouseholdData->getLatitude()));
        $comparison->setLatitude($this->compareScalarValue($storedHousehold->getLatitude(), $importedHouseholdData->getLatitude()));
        $comparison->setLatitude($this->compareScalarValue($storedHousehold->getLatitude(), $importedHouseholdData->getLatitude()));
        $comparison->setLongitude($this->compareScalarValue($storedHousehold->getLongitude(), $importedHouseholdData->getLongitude()));
        $comparison->setIncome($this->compareScalarValue($storedHousehold->getIncome(), $importedHouseholdData->getIncome()));
        $comparison->setFoodConsumptionScore($this->compareScalarValue($storedHousehold->getFoodConsumptionScore(), $importedHouseholdData->getFoodConsumptionScore()));
        $comparison->setCopingStrategiesIndex($this->compareScalarValue($storedHousehold->getCopingStrategiesIndex(), $importedHouseholdData->getCopingStrategiesIndex()));
        $comparison->setLocation($this->getLocation($importedHouseholdData, $storedHousehold));
        $comparison->setAdms($this->getAdms($importedHouseholdData, $storedHousehold));
        $comparison->setAdm1($this->getAdm1($importedHouseholdData, $storedHousehold));
        $comparison->setAdm2($this->getAdm2($importedHouseholdData, $storedHousehold));
        $comparison->setAdm3($this->getAdm3($importedHouseholdData, $storedHousehold));
        $comparison->setAdm4($this->getAdm4($importedHouseholdData, $storedHousehold));
        $comparison->setDebtLevel($this->compareScalarValue($storedHousehold->getDebtLevel(), $importedHouseholdData->getDebtLevel()));
        $comparison->setSupportReceivedTypes($this->compareScalarValue($storedHousehold->getSupportReceivedTypes(), $importedHouseholdData->getSupportReceivedTypes()));
        $comparison->setSupportOrganizationName($this->compareScalarValue($storedHousehold->getSupportOrganizationName(), $importedHouseholdData->getSupportOrganizationName()));
        $comparison->setSupportDateReceived($this->compareScalarValue($storedHousehold->getSupportDateReceived(), $importedHouseholdData->getSupportDateReceived()));
        $comparison->setIncomeSpentOnFood($this->compareScalarValue($storedHousehold->getSupportDateReceived(), $importedHouseholdData->getSupportDateReceived()));
        $comparison->setHouseholdIncome($this->compareScalarValue($storedHousehold->getHouseholdIncome(), $importedHouseholdData->getIncome()));
        $comparison->setEnumeratorName($this->compareScalarValue($storedHousehold->getEnumeratorName(), $importedHouseholdData->getEnumeratorName()));

        $comparison->setAssets($this->compareLists($storedHousehold->getAssets(), $importedHouseholdData->getAssets()));

        $currentAnswers = [];
        /** @var CountrySpecificAnswer $specificAnswer */
        foreach ($storedHousehold->getCountrySpecificAnswers() as $specificAnswer) {
            $currentAnswers[] = $specificAnswer->getCountrySpecific()->getFieldString().": ".$specificAnswer->getAnswer();
        }
        $importedAnswers = [];
        foreach ($importedHouseholdData->getCountrySpecificAnswers() as $specificAnswer) {
            $countrySpecific = $this->countrySpecificsRepository->find($specificAnswer->getCountrySpecificId());
            $importedAnswers[] = $countrySpecific->getFieldString().": ".$specificAnswer->getAnswer();
        }
        $comparison->setCountrySpecificAnswers($this->compareLists($currentAnswers, $importedAnswers));

        return $comparison;
    }

    public function compareBeneficiaries(Beneficiary $importedBeneficiary, \BeneficiaryBundle\Entity\Beneficiary $storedBeneficiary): BeneficiaryCompare
    {
        $beneficiaryImportedData = $importedBeneficiary->getInputType();
        $comparison = new BeneficiaryCompare();
        $comparison->setHouseholdId($this->compareScalarValue($storedBeneficiary->getHouseholdId(), -1)); // FIX THIS
        // $comparison->setHouseholdId($this->compareScalarValue(
        //         $importedBeneficiary->getHouseholdId(),
        //         $this->object->getBeneficiaryDuplicity()->getHouseholdDuplicity()->getTheirs()->getId()
        //     ));
        $localImportName = $beneficiaryImportedData->getLocalGivenName();
        if (!empty($beneficiaryImportedData->getLocalGivenName())) {
            $localImportName .= ' '.$beneficiaryImportedData->getLocalGivenName();
        }
        if (!empty($beneficiaryImportedData->getLocalFamilyName())) {
            $localImportName .= ' '.$beneficiaryImportedData->getLocalFamilyName();
        }
        $localDatabaseName = $storedBeneficiary->getLocalGivenName();
        if (!empty($storedBeneficiary->getLocalParentsName())) {
            $localDatabaseName .= ' '.$storedBeneficiary->getLocalParentsName();
        }
        if (!empty($storedBeneficiary->getLocalFamilyName())) {
            $localDatabaseName .= ' '.$storedBeneficiary->getLocalFamilyName();
        }
        $comparison->setLocalFullName($this->compareScalarValue($localDatabaseName, $localImportName));

        $enImportName = $beneficiaryImportedData->getEnGivenName();
        if (!empty($beneficiaryImportedData->getEnGivenName())) {
            $enImportName .= ' '.$beneficiaryImportedData->getEnGivenName();
        }
        if (!empty($beneficiaryImportedData->getEnFamilyName())) {
            $enImportName .= ' '.$beneficiaryImportedData->getEnFamilyName();
        }
        $enDatabaseName = $storedBeneficiary->getEnGivenName();
        if (!empty($storedBeneficiary->getEnParentsName())) {
            $enDatabaseName .= ' '.$storedBeneficiary->getEnParentsName();
        }
        if (!empty($storedBeneficiary->getEnFamilyName())) {
            $enDatabaseName .= ' '.$storedBeneficiary->getEnFamilyName();
        }
        $comparison->setEnglishFullName($this->compareScalarValue($enDatabaseName, $enImportName));

        $comparison->setGender($this->compareEnum(
            PersonGender::class,
            $storedBeneficiary->getPerson()->getGender(),
            $beneficiaryImportedData->getGender()
        ));
        $comparison->setDateOfBirth($this->compareScalarValue(
            $storedBeneficiary->getPerson()->getDateOfBirth()->format('Y-m-d'),
            $beneficiaryImportedData->getDateOfBirth()->format('Y-m-d')
        ));
        $phone = $storedBeneficiary->getPerson()->getPhones()->get(0);
        $databasePhone = $phone ? trim($phone->getPrefix().$phone->getNumber()) : null;
        $phone = $beneficiaryImportedData->getPhones()[0] ?? null;
        $importPhone = $phone ? trim($phone->getPrefix().$phone->getNumber()) : null;
        $comparison->setPhone1($this->compareScalarValue($databasePhone, !empty($importPhone) ? $importPhone : null));
        $phone = $storedBeneficiary->getPerson()->getPhones()->get(1);
        $databasePhone = $phone ? trim($phone->getPrefix().$phone->getNumber()) : null;
        $phone = $beneficiaryImportedData->getPhones()[1] ?? null;
        $importPhone = $phone ? trim($phone->getPrefix().$phone->getNumber()) : null;
        $comparison->setPhone2($this->compareScalarValue($databasePhone, !empty($importPhone) ? $importPhone : null));

        $databaseVulnerabilities = [];
        /** @var VulnerabilityCriterion $vulnerabilityCriterion */
        foreach ($storedBeneficiary->getVulnerabilityCriteria() as $vulnerabilityCriterion) {
            $databaseVulnerabilities[] = $vulnerabilityCriterion->getFieldString();
        }
        $comparison->setResidencyStatus($this->compareLists($databaseVulnerabilities, $beneficiaryImportedData->getVulnerabilityCriteria()));
        $comparison->setVulnerability($this->compareScalarValue($storedBeneficiary->getResidencyStatus(), $beneficiaryImportedData->getResidencyStatus()));

        return $comparison;
    }

    private function getLocation(HouseholdCreateInputType $importedHousehold, \BeneficiaryBundle\Entity\Household $storedHousehold): ?ScalarCompare
    {
        /** @var HouseholdLocation $householdLocation */
        $householdLocation = $storedHousehold->getHouseholdLocations()->first();
        $currentLocation = $householdLocation ? $this->serializeLocation($householdLocation) : null;

        $importLocation = null;
        if ($importedHousehold->getResidenceAddress()) {
            $location = $this->locationRepository->find($importedHousehold->getResidenceAddress()->getLocationId());
            $importLocation = HouseholdLocation::LOCATION_TYPE_RESIDENCE.": ".
                implode(' ', [
                    $importedHousehold->getResidenceAddress()->getStreet(),
                    $importedHousehold->getResidenceAddress()->getNumber(),
                    $importedHousehold->getResidenceAddress()->getPostcode(),
                    $location->getName(),
                ]);
        } else if ($importedHousehold->getTemporarySettlementAddress()) {
            $location = $this->locationRepository->find($importedHousehold->getTemporarySettlementAddress()->getLocationId());
            $importLocation = HouseholdLocation::LOCATION_TYPE_SETTLEMENT.": ".
                implode(' ', [
                    $importedHousehold->getTemporarySettlementAddress()->getStreet(),
                    $importedHousehold->getTemporarySettlementAddress()->getNumber(),
                    $importedHousehold->getTemporarySettlementAddress()->getPostcode(),
                    $location->getName(),
                ]);
        } else if ($importedHousehold->getCampAddress()) {
            $location = $this->locationRepository->find($importedHousehold->getCampAddress()->getCamp()->getLocationId());
            $importLocation = HouseholdLocation::LOCATION_TYPE_CAMP.": ".implode(' ', [
                    $importedHousehold->getCampAddress()->getCamp()->getName(),
                    $importedHousehold->getCampAddress()->getTentNumber(),
                    $location->getName(),
                ]);
        }
        return $this->compareScalarValue($currentLocation, $importLocation);
    }

    private function getAdms(HouseholdCreateInputType $importedHousehold, \BeneficiaryBundle\Entity\Household $storedHousehold): ?ScalarCompare
    {
        /** @var HouseholdLocation $householdLocation */
        $householdLocation = $storedHousehold->getHouseholdLocations()->first();
        $currentLocation = $householdLocation ? $this->serializeADMs($householdLocation->getLocation()) : null;
        $importLocation = $this->getImportedLocation($importedHousehold, $storedHousehold) ? $this->serializeADMs($this->getImportedLocation($importedHousehold, $storedHousehold)) : null;
        return $this->compareScalarValue($currentLocation, $importLocation);
    }
    private function serializeADMs(Location $location): string
    {
        $DELIMITER = ', ';
        if ($location->getParentLocation() !== null) {
            return $this->serializeADMs($location->getParentLocation()).$DELIMITER.$location->getName();
        }
        return $location->getName();
    }
    private function getAdm1(HouseholdCreateInputType $importedHousehold, \BeneficiaryBundle\Entity\Household $storedHousehold): ?ScalarCompare
    {
        /** @var HouseholdLocation $householdLocation */
        $householdLocation = $storedHousehold->getHouseholdLocations()->first();
        $currentLocation = $householdLocation ? $householdLocation->getLocation()->getAdm1Name() : null;
        $importLocation = $this->getImportedLocation($importedHousehold, $storedHousehold) ? $this->getImportedLocation($importedHousehold, $storedHousehold)->getAdm1Name() : null;
        return $this->compareScalarValue($currentLocation, $importLocation);
    }
    private function getAdm2(HouseholdCreateInputType $importedHousehold, \BeneficiaryBundle\Entity\Household $storedHousehold): ?ScalarCompare
    {
        /** @var HouseholdLocation $householdLocation */
        $householdLocation = $storedHousehold->getHouseholdLocations()->first();
        $currentLocation = $householdLocation ? $householdLocation->getLocation()->getAdm2Name() : null;
        $importLocation = $this->getImportedLocation($importedHousehold, $storedHousehold) ? $this->getImportedLocation($importedHousehold, $storedHousehold)->getAdm2Name() : null;
        return $this->compareScalarValue($currentLocation, $importLocation);
    }
    private function getAdm3(HouseholdCreateInputType $importedHousehold, \BeneficiaryBundle\Entity\Household $storedHousehold): ?ScalarCompare
    {
        /** @var HouseholdLocation $householdLocation */
        $householdLocation = $storedHousehold->getHouseholdLocations()->first();
        $currentLocation = $householdLocation ? $householdLocation->getLocation()->getAdm3Name() : null;
        $importLocation = $this->getImportedLocation($importedHousehold, $storedHousehold) ? $this->getImportedLocation($importedHousehold, $storedHousehold)->getAdm3Name() : null;
        return $this->compareScalarValue($currentLocation, $importLocation);
    }
    private function getAdm4(HouseholdCreateInputType $importedHousehold, \BeneficiaryBundle\Entity\Household $storedHousehold): ?ScalarCompare
    {
        /** @var HouseholdLocation $householdLocation */
        $householdLocation = $storedHousehold->getHouseholdLocations()->first();
        $currentLocation = $householdLocation ? $householdLocation->getLocation()->getAdm4Name() : null;
        $importLocation = $this->getImportedLocation($importedHousehold, $storedHousehold) ? $this->getImportedLocation($importedHousehold, $storedHousehold)->getAdm4Name() : null;
        return $this->compareScalarValue($currentLocation, $importLocation);
    }

    private function getImportedLocation(HouseholdCreateInputType $importedHousehold, \BeneficiaryBundle\Entity\Household $storedHousehold): ?Location
    {
        if ($importedHousehold->getResidenceAddress()) {
            return $this->locationRepository->find($importedHousehold->getResidenceAddress()->getLocationId());
        }
        if ($importedHousehold->getTemporarySettlementAddress()) {
            return $this->locationRepository->find($importedHousehold->getTemporarySettlementAddress()->getLocationId());
        }
        if ($importedHousehold->getCampAddress()) {
            return $this->locationRepository->find($importedHousehold->getCampAddress()->getCamp()->getLocationId());
        }
        return null;
    }

    private function serializeLocation(HouseholdLocation $householdLocation): ?string
    {
        if (!$householdLocation->getAddress()) {
            return $householdLocation->getType().": ".$householdLocation->getLocation()->getName();
        }
        $address = implode(' ', [
            $householdLocation->getAddress()->getStreet(),
            $householdLocation->getAddress()->getNumber(),
            $householdLocation->getAddress()->getPostcode(),
            $householdLocation->getLocation()->getName(),

        ]);
        return $householdLocation->getType().": ".$address;
    }
}
