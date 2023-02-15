<?php

declare(strict_types=1);

namespace InputType;

use DateTime;
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
use Validator\Constraints\Iso8601;
use Enum\Livelihood;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\GroupSequenceProviderInterface;
use Validator\Constraints\Enum;

#[Assert\GroupSequenceProvider]
class HouseholdUpdateInputType implements InputTypeInterface, GroupSequenceProviderInterface
{
    public function getGroupSequence(): array | GroupSequence
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
    private string $iso3;

    #[Enum(options: [
        'enumClass' => "Enum\Livelihood",
    ])]
    private ?string $livelihood = null;

    #[Assert\Type(['array', 'string'])]
    private array|string|null $assets = null;

    #[Enum(options: [
        'enumClass' => "Enum\HouseholdShelterStatus",
    ])]
    private int|string|null $shelterStatus = null;

    #[Assert\All(constraints: [new Assert\Type('integer', groups: ['Strict'])], groups: ['Strict'])]
    #[Assert\Type('array')]
    #[Assert\NotNull]
    private array $projectIds;

    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    private ?string $notes = null;

    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    private ?string $longitude = null;

    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    private ?string $latitude = null;

    #[Assert\Type('array')]
    #[Assert\Valid]
    private array $beneficiaries = [];

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private ?int $income = null;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private ?int $foodConsumptionScore = null;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private ?int $copingStrategiesIndex = null;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private ?int $debtLevel = null;

    #[Iso8601]
    private ?DateTime $supportDateReceived = null;

    #[Assert\Type(['array', 'string'])]
    private array|string|null $supportReceivedTypes = [];

    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    private ?string $supportOrganizationName = null;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private ?int $incomeSpentOnFood = null;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    private ?int $houseIncome = null;

    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    private ?string $enumeratorName = null;

    #[Assert\Valid]
    private ?ResidenceAddressInputType $residenceAddress = null;

    #[Assert\Valid]
    private ?TemporarySettlementAddressInputType $temporarySettlementAddress = null;

    #[Assert\Valid]
    private ?CampAddressInputType $campAddress = null;

    #[Assert\Type('array')]
    #[Assert\Valid]
    private array $countrySpecificAnswers = [];

    #[Assert\Type('string')]
    private ?string $proxyEnGivenName = null;

    #[Assert\Type('string')]
    private ?string $proxyEnFamilyName = null;

    #[Assert\Type('string')]
    private ?string $proxyEnParentsName = null;

    #[Assert\Type('string')]
    #[Assert\NotBlank(groups: ['Proxy'])]
    private ?string $proxyLocalGivenName = null;

    #[Assert\Type('string')]
    #[Assert\NotBlank(groups: ['Proxy'])]
    private ?string $proxyLocalFamilyName = null;

    #[Assert\Type('string')]
    private ?string $proxyLocalParentsName = null;

    #[Assert\Valid]
    private ?NationalIdCardInputType $proxyNationalIdCard = null;

    #[Assert\Valid]
    private ?PhoneInputType $proxyPhone = null;

    public function getIso3(): string
    {
        return $this->iso3;
    }

    public function setIso3(string $iso3): void
    {
        $this->iso3 = $iso3;
    }

    public function getLivelihood(): ?string
    {
        return $this->livelihood ? Livelihood::valueFromAPI($this->livelihood) : null;
    }

    public function setLivelihood(?string $livelihood): void
    {
        $this->livelihood = $livelihood;
    }

    #[Assert\All(
        constraints: [
            new Assert\Choice(callback: [HouseholdAssets::class, "values"], strict: true, groups: ['Strict']),
        ],
        groups: ['Strict']
    )]
    public function getAssets(): array
    {
        $enumBuilder = new EnumsBuilder(HouseholdAssets::class);
        $enumBuilder->setNullToEmptyArrayTransformation();

        return $enumBuilder->buildInputValues($this->assets);
    }

    /**
     * @param int[] $assets
     */
    public function setAssets(array|string|null $assets): void
    {
        $this->assets = $assets;
    }

    public function getShelterStatus(): ?string
    {
        return $this->shelterStatus ? HouseholdShelterStatus::valueFromAPI($this->shelterStatus) : null;
    }

    public function setShelterStatus(int | string | null $shelterStatus): void
    {
        $this->shelterStatus = $shelterStatus;
    }

    /**
     * @return int[]
     */
    public function getProjectIds(): array
    {
        return $this->projectIds;
    }

    public function setProjectIds(array $projectIds): void
    {
        $this->projectIds = $projectIds;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): void
    {
        $this->latitude = $latitude;
    }

    /**
     * @return BeneficiaryInputType[]
     */
    public function getBeneficiaries(): array
    {
        return $this->beneficiaries;
    }

    public function addBeneficiary(BeneficiaryInputType $beneficiary): void
    {
        $this->beneficiaries[] = $beneficiary;
    }

    public function removeBeneficiary(BeneficiaryInputType $beneficiary): void
    {
        // method must be declared to fullfill normalizer requirements
    }

    public function getIncome(): ?int
    {
        return $this->income;
    }

    public function setIncome(?int $income): void
    {
        $this->income = $income;
    }

    /**
     * Backward compatibility for API
     */
    public function setIncomeLevel(?int $income): void
    {
        $this->income = $income;
    }

    public function getFoodConsumptionScore(): ?int
    {
        return $this->foodConsumptionScore;
    }

    public function setFoodConsumptionScore(?int $foodConsumptionScore): void
    {
        $this->foodConsumptionScore = $foodConsumptionScore;
    }

    public function getCopingStrategiesIndex(): ?int
    {
        return $this->copingStrategiesIndex;
    }

    public function setCopingStrategiesIndex(?int $copingStrategiesIndex): void
    {
        $this->copingStrategiesIndex = $copingStrategiesIndex;
    }

    public function getDebtLevel(): ?int
    {
        return $this->debtLevel;
    }

    public function setDebtLevel(?int $debtLevel): void
    {
        $this->debtLevel = $debtLevel;
    }

    public function getSupportDateReceived(): ?DateTime
    {
        if (!$this->supportDateReceived) {
            return null;
        }

        return $this->supportDateReceived;
    }

    public function setSupportDateReceived(?DateTime $supportDateReceived): void
    {
        $this->supportDateReceived = $supportDateReceived;
    }

    /**
     * @return string[]
     */
    #[Assert\All(
        constraints: [
            new Assert\Choice(callback: [HouseholdSupportReceivedType::class, "values"], strict: true, groups: ['Strict']),
        ],
        groups: ['Strict']
    )]
    public function getSupportReceivedTypes(): array
    {
        $enumBuilder = new EnumsBuilder(HouseholdSupportReceivedType::class);
        $enumBuilder->setNullToEmptyArrayTransformation();

        return $enumBuilder->buildInputValues($this->supportReceivedTypes);
    }

    public function setSupportReceivedTypes(array|string|null $supportReceivedTypes): void
    {
        if (is_string($supportReceivedTypes)) {
            $supportReceivedTypes = explode(',', $supportReceivedTypes);
        }

        $this->supportReceivedTypes = $supportReceivedTypes;
    }

    public function getSupportOrganizationName(): ?string
    {
        return $this->supportOrganizationName;
    }

    public function setSupportOrganizationName(?string $supportOrganizationName): void
    {
        $this->supportOrganizationName = $supportOrganizationName;
    }

    public function getIncomeSpentOnFood(): ?int
    {
        return $this->incomeSpentOnFood;
    }

    public function setIncomeSpentOnFood(?int $incomeSpentOnFood): void
    {
        $this->incomeSpentOnFood = $incomeSpentOnFood;
    }

    public function getHouseIncome(): ?int
    {
        return $this->houseIncome;
    }

    public function setHouseIncome(?int $houseIncome): void
    {
        $this->houseIncome = $houseIncome;
    }

    public function getEnumeratorName(): ?string
    {
        return $this->enumeratorName;
    }

    public function setEnumeratorName(?string $enumeratorName): void
    {
        $this->enumeratorName = $enumeratorName;
    }

    public function getResidenceAddress(): ?ResidenceAddressInputType
    {
        return $this->residenceAddress;
    }

    public function setResidenceAddress(?ResidenceAddressInputType $address): void
    {
        $this->residenceAddress = $address;
    }

    public function getTemporarySettlementAddress(): ?TemporarySettlementAddressInputType
    {
        return $this->temporarySettlementAddress;
    }

    public function setTemporarySettlementAddress(?TemporarySettlementAddressInputType $address): void
    {
        $this->temporarySettlementAddress = $address;
    }

    public function getCampAddress(): ?CampAddressInputType
    {
        return $this->campAddress;
    }

    public function setCampAddress(?CampAddressInputType $address): void
    {
        $this->campAddress = $address;
    }

    /**
     * @return CountrySpecificsAnswerInputType[]
     */
    public function getCountrySpecificAnswers(): array
    {
        return $this->countrySpecificAnswers;
    }

    public function addCountrySpecificAnswer(CountrySpecificsAnswerInputType $inputType): void
    {
        $this->countrySpecificAnswers[] = $inputType;
    }

    public function removeCountrySpecificAnswer(CountrySpecificsAnswerInputType $inputType): void
    {
        // method must be declared to fullfill normalizer requirements
    }

    public function getProxyEnGivenName(): ?string
    {
        return $this->proxyEnGivenName;
    }

    public function setProxyEnGivenName(?string $proxyEnGivenName): void
    {
        $this->proxyEnGivenName = $proxyEnGivenName;
    }

    public function getProxyEnFamilyName(): ?string
    {
        return $this->proxyEnFamilyName;
    }

    public function setProxyEnFamilyName(?string $proxyEnFamilyName): void
    {
        $this->proxyEnFamilyName = $proxyEnFamilyName;
    }

    public function getProxyEnParentsName(): ?string
    {
        return $this->proxyEnParentsName;
    }

    public function setProxyEnParentsName(?string $proxyEnParentsName): void
    {
        $this->proxyEnParentsName = $proxyEnParentsName;
    }

    public function getProxyLocalGivenName(): ?string
    {
        return $this->proxyLocalGivenName;
    }

    public function setProxyLocalGivenName(?string $proxyLocalGivenName): void
    {
        $this->proxyLocalGivenName = $proxyLocalGivenName;
    }

    public function getProxyLocalFamilyName(): ?string
    {
        return $this->proxyLocalFamilyName;
    }

    public function setProxyLocalFamilyName(?string $proxyLocalFamilyName): void
    {
        $this->proxyLocalFamilyName = $proxyLocalFamilyName;
    }

    public function getProxyLocalParentsName(): ?string
    {
        return $this->proxyLocalParentsName;
    }

    public function setProxyLocalParentsName(?string $proxyLocalParentsName): void
    {
        $this->proxyLocalParentsName = $proxyLocalParentsName;
    }

    public function getProxyNationalIdCard(): ?NationalIdCardInputType
    {
        return $this->proxyNationalIdCard;
    }

    public function setProxyNationalIdCard(?NationalIdCardInputType $proxyNationalIdCard): void
    {
        $this->proxyNationalIdCard = $proxyNationalIdCard;
    }

    public function getProxyPhone(): ?PhoneInputType
    {
        return $this->proxyPhone;
    }

    public function setProxyPhone(?PhoneInputType $proxyPhone): void
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
