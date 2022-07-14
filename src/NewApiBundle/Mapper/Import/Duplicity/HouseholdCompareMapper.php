<?php declare(strict_types=1);

namespace NewApiBundle\Mapper\Import\Duplicity;

use NewApiBundle\Entity\CountrySpecificAnswer;
use NewApiBundle\Entity\HouseholdLocation;
use BeneficiaryBundle\Repository\CountrySpecificRepository;
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
    public function geLocations(): ?array
    {
        $currentLocations = [];
        /** @var HouseholdLocation $householdLocation */
        foreach ($this->object->getCurrent()->getHouseholdLocations() as $householdLocation) {
            $currentLocations[] = $this->serializeLocation($householdLocation);
        }
        $importLocations = [];
        if ($this->object->getImported()->getTemporarySettlementAddress()) {
            $locationName = $this->locationRepository->find($this->object->getImported()->getTemporarySettlementAddress()->getLocationId());
            $importLocations[] = HouseholdLocation::LOCATION_TYPE_SETTLEMENT.": ".
                implode(' ', [
                $this->object->getImported()->getTemporarySettlementAddress()->getStreet(),
                $this->object->getImported()->getTemporarySettlementAddress()->getNumber(),
                $this->object->getImported()->getTemporarySettlementAddress()->getPostcode(),
                $locationName,
            ]);
        }
        if ($this->object->getImported()->getCampAddress()) {
            $locationName = $this->locationRepository->find($this->object->getImported()->getCampAddress()->getCamp()->getLocationId());
            $importLocations[] = HouseholdLocation::LOCATION_TYPE_CAMP.": ".implode(' ', [
                    $this->object->getImported()->getCampAddress()->getCamp()->getName(),
                    $this->object->getImported()->getCampAddress()->getTentNumber(),
                    $locationName,
                ]);
        }
        return $this->compareLists($currentLocations, $importLocations);
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
