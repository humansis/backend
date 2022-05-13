<?php declare(strict_types=1);

namespace NewApiBundle\Mapper\Import\Duplicity;

use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\HouseholdLocation;
use BeneficiaryBundle\Repository\CountrySpecificRepository;
use CommonBundle\Entity\Location;
use CommonBundle\Repository\LocationRepository;
use NewApiBundle\Component\Import\Finishing\HouseholdDecoratorBuilder;
use NewApiBundle\Component\Import\ValueObject\HouseholdCompare;
use NewApiBundle\InputType\HouseholdCreateInputType;
use NewApiBundle\Serializer\MapperInterface;

class HouseholdCompareMapper implements MapperInterface
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

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof HouseholdCompare && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof HouseholdCompare) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.HouseholdCompare::class.', '.get_class($object).' given.');
    }

    // livelihood
    public function getLivelihood(): ?array
    {
        return $this->compareScalarValue($this->object->getCurrent()->getLivelihood(), $this->object->getImported()->getLivelihood());
    }
    // assets
    public function getAssets(): ?array
    {
        return $this->compareLists($this->object->getCurrent()->getAssets(), $this->object->getImported()->getAssets());
    }
    // shelterStatus
    public function getShelterStatus(): ?array
    {
        return $this->compareScalarValue($this->object->getCurrent()->getShelterStatus(), $this->object->getImported()->getShelterStatus());
    }
    // notes
    public function getNotes(): ?array
    {
        return $this->compareScalarValue($this->object->getCurrent()->getNotes(), $this->object->getImported()->getNotes());
    }
    // latitude
    public function getLatitude(): ?array
    {
        return $this->compareScalarValue($this->object->getCurrent()->getLatitude(), $this->object->getImported()->getLatitude());
    }
    // longitude
    public function getLongitude(): ?array
    {
        return $this->compareScalarValue($this->object->getCurrent()->getLongitude(), $this->object->getImported()->getLongitude());
    }
    // countrySpecificAnswers
    public function getCountrySpecificAnswers(): ?array
    {
        $currentAnswers = [];
        /** @var CountrySpecificAnswer $specificAnswer */
        foreach ($this->object->getCurrent()->getCountrySpecificAnswers() as $specificAnswer) {
            $currentAnswers[] = $specificAnswer->getCountrySpecific()->getFieldString().": ".$specificAnswer->getAnswer();
        }
        $importedAnswers = [];
        foreach ($this->object->getImported()->getCountrySpecificAnswers() as $specificAnswer) {
            $countrySpecific = $this->countrySpecificsRepository->find($specificAnswer->getCountrySpecificId());
            $importedAnswers[] = $countrySpecific->getFieldString().": ".$specificAnswer->getAnswer();
        }
        return $this->compareLists($currentAnswers, $importedAnswers);
    }
    // income
    public function getIncome(): ?array
    {
        return $this->compareScalarValue($this->object->getCurrent()->getIncome(), $this->object->getImported()->getIncome());
    }
    // foodConsumptionScore
    public function getFoodConsumptionScore(): ?array
    {
        return $this->compareScalarValue($this->object->getCurrent()->getFoodConsumptionScore(), $this->object->getImported()->getFoodConsumptionScore());
    }
    // copingStrategiesIndex
    public function getCopingStrategiesIndex(): ?array
    {
        return $this->compareScalarValue($this->object->getCurrent()->getCopingStrategiesIndex(), $this->object->getImported()->getCopingStrategiesIndex());
    }
    // householdLocations
    public function getLocation(): ?array
    {
        /** @var HouseholdLocation $householdLocation */
        $householdLocation = $this->object->getCurrent()->getHouseholdLocations()->first();
        $currentLocation = $householdLocation ? $this->serializeLocation($householdLocation) : null;

        $importLocation = null;
        if ($this->object->getImported()->getTemporarySettlementAddress()) {
            $locationName = $this->locationRepository->find($this->object->getImported()->getTemporarySettlementAddress()->getLocationId());
            $importLocation = HouseholdLocation::LOCATION_TYPE_SETTLEMENT.": ".
                implode(' ', [
                $this->object->getImported()->getTemporarySettlementAddress()->getStreet(),
                $this->object->getImported()->getTemporarySettlementAddress()->getNumber(),
                $this->object->getImported()->getTemporarySettlementAddress()->getPostcode(),
                $locationName,
            ]);
        } else if ($this->object->getImported()->getCampAddress()) {
            $locationName = $this->locationRepository->find($this->object->getImported()->getCampAddress()->getCamp()->getLocationId());
            $importLocation = HouseholdLocation::LOCATION_TYPE_CAMP.": ".implode(' ', [
                    $this->object->getImported()->getCampAddress()->getCamp()->getName(),
                    $this->object->getImported()->getCampAddress()->getTentNumber(),
                    $locationName,
                ]);
        }
        return $this->compareScalarValue($currentLocation, $importLocation);
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
    public function getAdms(): ?array
    {
        /** @var HouseholdLocation $householdLocation */
        $householdLocation = $this->object->getCurrent()->getHouseholdLocations()->first();
        $currentLocation = $householdLocation ? $this->serializeADMs($householdLocation->getLocation()) : null;

        $importLocation = null;
        if ($this->object->getImported()->getTemporarySettlementAddress()) {
            $location = $this->locationRepository->find($this->object->getImported()->getTemporarySettlementAddress()->getLocationId());
            $importLocation = $this->serializeADMs($location);
        } else if ($this->object->getImported()->getCampAddress()) {
            $location = $this->locationRepository->find($this->object->getImported()->getCampAddress()->getCamp()->getLocationId());
            $importLocation = $this->serializeADMs($location);
        }
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
    public function getAdm1(): ?array
    {
        /** @var HouseholdLocation $householdLocation */
        $householdLocation = $this->object->getCurrent()->getHouseholdLocations()->first();
        $currentLocation = $householdLocation ? $householdLocation->getLocation()->getAdm1Name() : null;

        $importLocation = null;
        if ($this->object->getImported()->getTemporarySettlementAddress()) {
            $location = $this->locationRepository->find($this->object->getImported()->getTemporarySettlementAddress()->getLocationId());
            $importLocation = $location->getAdm1Name();
        } else if ($this->object->getImported()->getCampAddress()) {
            $location = $this->locationRepository->find($this->object->getImported()->getCampAddress()->getCamp()->getLocationId());
            $importLocation = $location->getAdm1Name();
        }
        return $this->compareScalarValue($currentLocation, $importLocation);
    }
    public function getAdm2(): ?array
    {
        /** @var HouseholdLocation $householdLocation */
        $householdLocation = $this->object->getCurrent()->getHouseholdLocations()->first();
        $currentLocation = $householdLocation ? $householdLocation->getLocation()->getAdm2Name() : null;

        $importLocation = null;
        if ($this->object->getImported()->getTemporarySettlementAddress()) {
            $location = $this->locationRepository->find($this->object->getImported()->getTemporarySettlementAddress()->getLocationId());
            $importLocation = $location->getAdm2Name();
        } else if ($this->object->getImported()->getCampAddress()) {
            $location = $this->locationRepository->find($this->object->getImported()->getCampAddress()->getCamp()->getLocationId());
            $importLocation = $location->getAdm2Name();
        }
        return $this->compareScalarValue($currentLocation, $importLocation);
    }
    public function getAdm3(): ?array
    {
        /** @var HouseholdLocation $householdLocation */
        $householdLocation = $this->object->getCurrent()->getHouseholdLocations()->first();
        $currentLocation = $householdLocation ? $householdLocation->getLocation()->getAdm3Name() : null;

        $importLocation = null;
        if ($this->object->getImported()->getTemporarySettlementAddress()) {
            $location = $this->locationRepository->find($this->object->getImported()->getTemporarySettlementAddress()->getLocationId());
            $importLocation = $location->getAdm3Name();
        } else if ($this->object->getImported()->getCampAddress()) {
            $location = $this->locationRepository->find($this->object->getImported()->getCampAddress()->getCamp()->getLocationId());
            $importLocation = $location->getAdm3Name();
        }
        return $this->compareScalarValue($currentLocation, $importLocation);
    }
    public function getAdm4(): ?array
    {
        /** @var HouseholdLocation $householdLocation */
        $householdLocation = $this->object->getCurrent()->getHouseholdLocations()->first();
        $currentLocation = $householdLocation ? $householdLocation->getLocation()->getAdm4Name() : null;

        $importLocation = null;
        if ($this->object->getImported()->getTemporarySettlementAddress()) {
            $location = $this->locationRepository->find($this->object->getImported()->getTemporarySettlementAddress()->getLocationId());
            $importLocation = $location->getAdm4Name();
        } else if ($this->object->getImported()->getCampAddress()) {
            $location = $this->locationRepository->find($this->object->getImported()->getCampAddress()->getCamp()->getLocationId());
            $importLocation = $location->getAdm4Name();
        }
        return $this->compareScalarValue($currentLocation, $importLocation);
    }

    // debtLevel
    public function getDebtLevel(): ?array
    {
        return $this->compareScalarValue($this->object->getCurrent()->getDebtLevel(), $this->object->getImported()->getDebtLevel());
    }
    // supportReceivedTypes
    public function getSupportReceivedTypes(): ?array
    {
        return $this->compareScalarValue($this->object->getCurrent()->getSupportReceivedTypes(), $this->object->getImported()->getSupportReceivedTypes());
    }
    // supportOrganizationName
    public function getSupportOrganizationName(): ?array
    {
        return $this->compareScalarValue($this->object->getCurrent()->getSupportOrganizationName(), $this->object->getImported()->getSupportOrganizationName());
    }
    // supportDateReceived
    public function getSupportDateReceived(): ?array
    {
        return $this->compareScalarValue($this->object->getCurrent()->getSupportDateReceived(), $this->object->getImported()->getSupportDateReceived());
    }
    // incomeSpentOnFood
    public function getIncomeSpentOnFood(): ?array
    {
        return $this->compareScalarValue($this->object->getCurrent()->getIncomeSpentOnFood(), $this->object->getImported()->getIncomeSpentOnFood());
    }
    // householdIncome
    public function getHouseholdIncome(): ?array
    {
        return $this->compareScalarValue($this->object->getCurrent()->getHouseholdIncome(), $this->object->getImported()->getHouseIncome());
    }
    // enumeratorName
    public function getEnumeratorName(): ?array
    {
        return $this->compareScalarValue($this->object->getCurrent()->getEnumeratorName(), $this->object->getImported()->getEnumeratorName());
    }

}
