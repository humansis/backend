<?php

declare(strict_types=1);

namespace InputType;

use DateTimeInterface;
use Enum\HouseholdAssets;
use Enum\HouseholdShelterStatus;
use Enum\HouseholdSupportReceivedType;
use InputType\Beneficiary\Address\CampAddressInputType;
use InputType\Beneficiary\Address\ResidenceAddressInputType;
use InputType\Beneficiary\Address\TemporarySettlementAddressInputType;
use InputType\Beneficiary\BeneficiaryInputType;
use InputType\Beneficiary\CountrySpecificsAnswerInputType;
use InputType\Beneficiary\NationalIdCardInputType;
use InputType\Beneficiary\PhoneInputType;
use InputType\Helper\EnumsBuilder;
use Exception\MissingHouseholdHeadException;
use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Utils\DateTime\Iso8601Converter;
use Validator\Constraints\Iso8601;
use Enum\Livelihood;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\GroupSequenceProviderInterface;
use Validator\Constraints\Enum;

#[Assert\GroupSequenceProvider]
class HouseholdUpdateInputType implements InputTypeInterface, GroupSequenceProviderInterface
{
    public function getGroupSequence(): array|GroupSequence
    {
        $commonSequence = [
            'HouseholdUpdateInputType',
            'Strict',
        ];

        $proxyParameters = [
            $this->getProxyLocalGivenName(),
            $this->getProxyLocalFamilyName(),
            $this->getProxyLocalParentsName(),
            $this->getProxyEnGivenName(),
            $this->getProxyEnFamilyName(),
            $this->getProxyEnParentsName(),
            $this->getProxyPhone(),
            $this->getProxyNationalIdCard(),
        ];

        foreach ($proxyParameters as $proxyParameter) {
            if (null !== $proxyParameter) {
                $commonSequence[] = 'Proxy';
                break;
            }
        }

        return $commonSequence;
    }

    /**
     * @deprecated
     */
    private $iso3;

    /**
     * @Enum(enumClass="Enum\Livelihood")
     */
    private $livelihood;

    #[Assert\Type(['array', 'string'])]
    private $assets;

    /**
     * @Enum(enumClass="Enum\HouseholdShelterStatus")
     */
    private $shelterStatus;

    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    #[Assert\Type('array')]
    #[Assert\NotNull]
    private $projectIds;

    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    private $notes;

    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    private $longitude;

    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    private $latitude;

    #[Assert\Type('array')]
    #[Assert\Valid]
    private array $beneficiaries = []; #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private $income;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private $foodConsumptionScore;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private $copingStrategiesIndex;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private $debtLevel;

    /**
     * @Iso8601
     */
    private $supportDateReceived;

    #[Assert\Type(['array', 'string'])]
    private array $supportReceivedTypes = [];

    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    private $supportOrganizationName;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private $incomeSpentOnFood;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private $houseIncome;

    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    private $enumeratorName;

    #[Assert\Valid]
    private ?\InputType\Beneficiary\Address\ResidenceAddressInputType $residenceAddress = null;

    #[Assert\Valid]
    private ?\InputType\Beneficiary\Address\TemporarySettlementAddressInputType $temporarySettlementAddress = null;

    #[Assert\Valid]
    private ?\InputType\Beneficiary\Address\CampAddressInputType $campAddress = null;

    #[Assert\Type('array')]
    #[Assert\Valid]
    private array $countrySpecificAnswers = [];

    #[Assert\Type('string')]
    private $proxyEnGivenName;

    #[Assert\Type('string')]
    private $proxyEnFamilyName;

    #[Assert\Type('string')]
    private $proxyEnParentsName;

    #[Assert\Type('string')]
    #[Assert\NotBlank(groups: ['Proxy'])]
    private $proxyLocalGivenName;

    #[Assert\Type('string')]
    #[Assert\NotBlank(groups: ['Proxy'])]
    private $proxyLocalFamilyName;

    #[Assert\Type('string')]
    private $proxyLocalParentsName;

    #[Assert\Valid]
    private ?\InputType\Beneficiary\NationalIdCardInputType $proxyNationalIdCard = null;

    #[Assert\Valid]
    private ?\InputType\Beneficiary\PhoneInputType $proxyPhone = null;

    /**
     * @return string
     */
    public function getIso3()
    {
        return $this->iso3;
    }

    /**
     * @param string $iso3
     */
    public function setIso3($iso3)
    {
        $this->iso3 = $iso3;
    }

    /**
     * @return string|null
     */
    public function getLivelihood()
    {
        return $this->livelihood ? Livelihood::valueFromAPI($this->livelihood) : null;
    }

    /**
     * @param string|null $livelihood
     */
    public function setLivelihood($livelihood)
    {
        $this->livelihood = $livelihood;
    }

    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Choice(callback={"\Enum\HouseholdAssets", "values"}, strict=true, groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     *
     * @return string[]
     */
    public function getAssets(): array
    {
        $enumBuilder = new EnumsBuilder(HouseholdAssets::class);
        $enumBuilder->setNullToEmptyArrayTransformation();

        return $enumBuilder->buildInputValues($this->assets);
    }

    /**
     * @param int[] $assets
     */
    public function setAssets($assets)
    {
        $this->assets = $assets;
    }

    /**
     * @return string|null
     */
    public function getShelterStatus()
    {
        return $this->shelterStatus ? HouseholdShelterStatus::valueFromAPI($this->shelterStatus) : null;
    }

    /**
     * @param int|string|null $shelterStatus
     */
    public function setShelterStatus($shelterStatus)
    {
        $this->shelterStatus = $shelterStatus;
    }

    /**
     * @return int[]
     */
    public function getProjectIds()
    {
        return $this->projectIds;
    }

    /**
     * @param int[] $projectIds
     */
    public function setProjectIds($projectIds)
    {
        $this->projectIds = $projectIds;
    }

    /**
     * @return string|null
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string|null $notes
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
    }

    /**
     * @return string|null
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param string|null $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }

    /**
     * @return string|null
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param string|null $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    /**
     * @return BeneficiaryInputType[]
     */
    public function getBeneficiaries()
    {
        return $this->beneficiaries;
    }

    public function addBeneficiary(BeneficiaryInputType $beneficiary)
    {
        $this->beneficiaries[] = $beneficiary;
    }

    public function removeBeneficiary(BeneficiaryInputType $beneficiary)
    {
        // method must be declared to fullfill normalizer requirements
    }

    /**
     * @return int|null
     */
    public function getIncome()
    {
        return $this->income;
    }

    /**
     * @param int|null $income
     */
    public function setIncome($income)
    {
        $this->income = $income;
    }

    /**
     * Backward compatibility for API
     *
     * @param int|null $income
     */
    public function setIncomeLevel($income)
    {
        $this->income = $income;
    }

    /**
     * @return int|null
     */
    public function getFoodConsumptionScore()
    {
        return $this->foodConsumptionScore;
    }

    /**
     * @param int|null $foodConsumptionScore
     */
    public function setFoodConsumptionScore($foodConsumptionScore)
    {
        $this->foodConsumptionScore = $foodConsumptionScore;
    }

    /**
     * @return int|null
     */
    public function getCopingStrategiesIndex()
    {
        return $this->copingStrategiesIndex;
    }

    /**
     * @param int|null $copingStrategiesIndex
     */
    public function setCopingStrategiesIndex($copingStrategiesIndex)
    {
        $this->copingStrategiesIndex = $copingStrategiesIndex;
    }

    /**
     * @return int|null
     */
    public function getDebtLevel()
    {
        return $this->debtLevel;
    }

    /**
     * @param int|null $debtLevel
     */
    public function setDebtLevel($debtLevel)
    {
        $this->debtLevel = $debtLevel;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getSupportDateReceived(): ?DateTimeInterface
    {
        if (!$this->supportDateReceived) {
            return null;
        }

        return Iso8601Converter::toDateTime($this->supportDateReceived) ?: null;
    }

    /**
     * @param string|null $supportDateReceived
     */
    public function setSupportDateReceived($supportDateReceived)
    {
        $this->supportDateReceived = $supportDateReceived;
    }

    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Choice(callback={"\Enum\HouseholdSupportReceivedType", "values"}, strict=true, groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     *
     * @return string[]
     */
    public function getSupportReceivedTypes(): array
    {
        $enumBuilder = new EnumsBuilder(HouseholdSupportReceivedType::class);
        $enumBuilder->setNullToEmptyArrayTransformation();

        return $enumBuilder->buildInputValues($this->supportReceivedTypes);
    }

    /**
     * @param array|null $supportReceivedTypes
     */
    public function setSupportReceivedTypes($supportReceivedTypes)
    {
        $this->supportReceivedTypes = $supportReceivedTypes;
    }

    /**
     * @return string|null
     */
    public function getSupportOrganizationName()
    {
        return $this->supportOrganizationName;
    }

    /**
     * @param string|null $supportOrganizationName
     */
    public function setSupportOrganizationName($supportOrganizationName)
    {
        $this->supportOrganizationName = $supportOrganizationName;
    }

    /**
     * @return int|null
     */
    public function getIncomeSpentOnFood()
    {
        return $this->incomeSpentOnFood;
    }

    /**
     * @param int|null $incomeSpentOnFood
     */
    public function setIncomeSpentOnFood($incomeSpentOnFood)
    {
        $this->incomeSpentOnFood = $incomeSpentOnFood;
    }

    /**
     * @return int|null
     */
    public function getHouseIncome()
    {
        return $this->houseIncome;
    }

    /**
     * @param int|null $houseIncome
     */
    public function setHouseIncome($houseIncome)
    {
        $this->houseIncome = $houseIncome;
    }

    /**
     * @return string|null
     */
    public function getEnumeratorName()
    {
        return $this->enumeratorName;
    }

    /**
     * @param string|null $enumeratorName
     */
    public function setEnumeratorName($enumeratorName)
    {
        $this->enumeratorName = $enumeratorName;
    }

    /**
     * @return ResidenceAddressInputType|null
     */
    public function getResidenceAddress()
    {
        return $this->residenceAddress;
    }

    /**
     * @param ResidenceAddressInputType|null $address
     */
    public function setResidenceAddress(ResidenceAddressInputType $address)
    {
        $this->residenceAddress = $address;
    }

    /**
     * @return TemporarySettlementAddressInputType|null
     */
    public function getTemporarySettlementAddress()
    {
        return $this->temporarySettlementAddress;
    }

    /**
     * @param TemporarySettlementAddressInputType|null $address
     */
    public function setTemporarySettlementAddress(TemporarySettlementAddressInputType $address)
    {
        $this->temporarySettlementAddress = $address;
    }

    /**
     * @return CampAddressInputType|null
     */
    public function getCampAddress()
    {
        return $this->campAddress;
    }

    /**
     * @param CampAddressInputType|null $address
     */
    public function setCampAddress(CampAddressInputType $address)
    {
        $this->campAddress = $address;
    }

    /**
     * @return CountrySpecificsAnswerInputType[]
     */
    public function getCountrySpecificAnswers()
    {
        return $this->countrySpecificAnswers;
    }

    public function addCountrySpecificAnswer(CountrySpecificsAnswerInputType $inputType)
    {
        $this->countrySpecificAnswers[] = $inputType;
    }

    /**
     * @param CountrySpecificsAnswerInputType $beneficiary
     */
    public function removeCountrySpecificAnswer(CountrySpecificsAnswerInputType $inputType)
    {
        // method must be declared to fullfill normalizer requirements
    }

    /**
     * @return string|null
     */
    public function getProxyEnGivenName()
    {
        return $this->proxyEnGivenName;
    }

    /**
     * @param string|null $proxyEnGivenName
     */
    public function setProxyEnGivenName($proxyEnGivenName)
    {
        $this->proxyEnGivenName = $proxyEnGivenName;
    }

    /**
     * @return string|null
     */
    public function getProxyEnFamilyName()
    {
        return $this->proxyEnFamilyName;
    }

    /**
     * @param string|null $proxyEnFamilyName
     */
    public function setProxyEnFamilyName($proxyEnFamilyName)
    {
        $this->proxyEnFamilyName = $proxyEnFamilyName;
    }

    /**
     * @return string|null
     */
    public function getProxyEnParentsName()
    {
        return $this->proxyEnParentsName;
    }

    /**
     * @param string|null $proxyEnParentsName
     */
    public function setProxyEnParentsName($proxyEnParentsName)
    {
        $this->proxyEnParentsName = $proxyEnParentsName;
    }

    /**
     * @return string|null
     */
    public function getProxyLocalGivenName()
    {
        return $this->proxyLocalGivenName;
    }

    /**
     * @param string|null $proxyLocalGivenName
     */
    public function setProxyLocalGivenName($proxyLocalGivenName)
    {
        $this->proxyLocalGivenName = $proxyLocalGivenName;
    }

    /**
     * @return string|null
     */
    public function getProxyLocalFamilyName()
    {
        return $this->proxyLocalFamilyName;
    }

    /**
     * @param string|null $proxyLocalFamilyName
     */
    public function setProxyLocalFamilyName($proxyLocalFamilyName)
    {
        $this->proxyLocalFamilyName = $proxyLocalFamilyName;
    }

    /**
     * @return string|null
     */
    public function getProxyLocalParentsName()
    {
        return $this->proxyLocalParentsName;
    }

    /**
     * @param string|null $proxyLocalParentsName
     */
    public function setProxyLocalParentsName($proxyLocalParentsName)
    {
        $this->proxyLocalParentsName = $proxyLocalParentsName;
    }

    /**
     * @return NationalIdCardInputType|null
     */
    public function getProxyNationalIdCard()
    {
        return $this->proxyNationalIdCard;
    }

    public function setProxyNationalIdCard(?NationalIdCardInputType $proxyNationalIdCard)
    {
        $this->proxyNationalIdCard = $proxyNationalIdCard;
    }

    /**
     * @return PhoneInputType|null
     */
    public function getProxyPhone()
    {
        return $this->proxyPhone;
    }

    public function setProxyPhone(?PhoneInputType $proxyPhone)
    {
        $this->proxyPhone = $proxyPhone;
    }

    /**
     * @return int
     */
    #[Assert\EqualTo(1)]
    public function getBeneficiaryHeadCount(): int
    {
        $headCount = 0;
        foreach ($this->getBeneficiaries() as $beneficiaryInputType) {
            if ($beneficiaryInputType->isHead()) {
                $headCount++;
            }
        }

        return $headCount;
    }

    public function hasProxy(): bool
    {
        return null !== $this->getProxyLocalGivenName()
            && null !== $this->getProxyLocalFamilyName();
    }

    public function getHouseholdHead(): BeneficiaryInputType
    {
        foreach ($this->getBeneficiaries() as $beneficiaryInputType) {
            if ($beneficiaryInputType->isHead()) {
                return $beneficiaryInputType;
            }
        }
        throw new MissingHouseholdHeadException('There must be head');
    }
}