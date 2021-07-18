<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use BeneficiaryBundle\Entity\Household;
use NewApiBundle\InputType\Beneficiary\Address\CampAddressInputType;
use NewApiBundle\InputType\Beneficiary\Address\ResidenceAddressInputType;
use NewApiBundle\InputType\Beneficiary\Address\TemporarySettlementAddressInputType;
use NewApiBundle\InputType\Beneficiary\BeneficiaryInputType;
use NewApiBundle\InputType\Beneficiary\CountrySpecificsAnswerInputType;
use NewApiBundle\InputType\Beneficiary\NationalIdCardInputType;
use NewApiBundle\InputType\Beneficiary\PhoneInputType;
use NewApiBundle\Request\InputTypeInterface;
use NewApiBundle\Validator\Constraints\Country;
use NewApiBundle\Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

/**
 * @Assert\GroupSequenceProvider()
 */
class HouseholdUpdateInputType implements InputTypeInterface, GroupSequenceProviderInterface
{
    public function getGroupSequence()
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
     * @Country
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $iso3;

    /**
     * @Assert\Choice(callback={"ProjectBundle\Enum\Livelihood", "values"}, strict=true)
     */
    private $livelihood;

    /**
     * @Assert\Type("array")
     * @Assert\NotNull
     * @Assert\All(
     *     constraints={
     *         @Assert\Choice(callback="assets", strict=true, groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    private $assets;

    /**
     * @Assert\Choice(callback="shelterStatuses", strict=true)
     */
    private $shelterStatus;

    /**
     * @Assert\Type("array")
     * @Assert\NotNull
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    private $projectIds;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    private $notes;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    private $longitude;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    private $latitude;

    /**
     * @Assert\Type("array")
     * @Assert\Valid
     */
    private $beneficiaries = []; // todo validate only one head is allowed

    /**
     * @Assert\Type("integer")
     * @Assert\GreaterThanOrEqual("0")
     */
    private $incomeLevel;

    /**
     * @Assert\Type("integer")
     * @Assert\GreaterThanOrEqual("0")
     */
    private $foodConsumptionScore;

    /**
     * @Assert\Type("integer")
     * @Assert\GreaterThanOrEqual("0")
     */
    private $copingStrategiesIndex;

    /**
     * @Assert\Type("integer")
     * @Assert\GreaterThanOrEqual("0")
     */
    private $debtLevel;

    /**
     * @Iso8601
     */
    private $supportDateReceived;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Choice(callback="supportReceivedTypes", strict=true, groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    private $supportReceivedTypes = [];

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    private $supportOrganizationName;

    /**
     * @Assert\Type("integer")
     * @Assert\GreaterThanOrEqual("0")
     */
    private $incomeSpentOnFood;

    /**
     * @Assert\Type("integer")
     * @Assert\GreaterThanOrEqual("0")
     */
    private $houseIncome;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    private $enumeratorName;

    /**
     * @Assert\Valid
     */
    private $residenceAddress;

    /**
     * @Assert\Valid
     */
    private $temporarySettlementAddress;

    /**
     * @Assert\Valid
     */
    private $campAddress;

    /**
     * @Assert\Type("array")
     * @Assert\Valid
     */
    private $countrySpecificAnswers = [];

    /**
     * @Assert\Type("string")
     */
    private $proxyEnGivenName;

    /**
     * @Assert\Type("string")
     */
    private $proxyEnFamilyName;

    /**
     * @Assert\Type("string")
     */
    private $proxyEnParentsName;

    /**
     * @Assert\Type("string")
     * @Assert\NotBlank(groups={"Proxy"})
     */
    private $proxyLocalGivenName;

    /**
     * @Assert\Type("string")
     * @Assert\NotBlank(groups={"Proxy"})
     */
    private $proxyLocalFamilyName;

    /**
     * @Assert\Type("string")
     */
    private $proxyLocalParentsName;

    /**
     * @Assert\Valid
     */
    private $proxyNationalIdCard;

    /**
     * @Assert\Valid
     */
    private $proxyPhone;

    final public static function assets()
    {
        $keys = [];
        foreach (Household::ASSETS as $key => $value) {
            $keys[] = (int) $key;
        }

        return $keys;
    }

    final public static function shelterStatuses()
    {
        $keys = [];
        foreach (Household::SHELTER_STATUSES as $key => $value) {
            $keys[] = (int) $key;
        }

        return $keys;
    }

    final public static function supportReceivedTypes()
    {
        $keys = [];
        foreach (Household::SUPPORT_RECIEVED_TYPES as $key => $value) {
            $keys[] = (string) $key;
        }

        return $keys;
    }

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
     * @return int|null
     */
    public function getLivelihood()
    {
        return $this->livelihood;
    }

    /**
     * @param int|null $livelihood
     */
    public function setLivelihood($livelihood)
    {
        $this->livelihood = $livelihood;
    }

    /**
     * @return int[]
     */
    public function getAssets()
    {
        return $this->assets;
    }

    /**
     * @param int[] $assets
     */
    public function setAssets(array $assets)
    {
        foreach ($assets as $asset) {
            $this->assets[] = (int) $asset;
        }
    }

    /**
     * @return int|null
     */
    public function getShelterStatus()
    {
        return $this->shelterStatus;
    }

    /**
     * @param int|null $shelterStatus
     */
    public function setShelterStatus($shelterStatus)
    {
        $this->shelterStatus = (int) $shelterStatus;
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

    /**
     * @param BeneficiaryInputType $beneficiary
     */
    public function addBeneficiary(BeneficiaryInputType $beneficiary)
    {
        $this->beneficiaries[] = $beneficiary;
    }

    /**
     * @param BeneficiaryInputType $beneficiary
     */
    public function removeBeneficiary(BeneficiaryInputType $beneficiary)
    {
        // method must be declared to fullfill normalizer requirements
    }

    /**
     * @return int|null
     */
    public function getIncomeLevel()
    {
        return $this->incomeLevel;
    }

    /**
     * @param int|null $incomeLevel
     */
    public function setIncomeLevel($incomeLevel)
    {
        $this->incomeLevel = $incomeLevel;
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
     * @return \DateTime|null
     * @throws \Exception
     */
    public function getSupportDateReceived()
    {
        return $this->supportDateReceived ? new \DateTime($this->supportDateReceived) : null;
    }

    /**
     * @param string|null $supportDateReceived
     */
    public function setSupportDateReceived($supportDateReceived)
    {
        $this->supportDateReceived = $supportDateReceived;
    }

    /**
     * @return int|null
     */
    public function getSupportReceivedTypes()
    {
        return $this->supportReceivedTypes;
    }

    /**
     * @param int|null $supportReceivedTypes
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

    /**
     * @param CountrySpecificsAnswerInputType $inputType
     */
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

    /**
     * @param NationalIdCardInputType|null $proxyNationalIdCard
     */
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

    /**
     * @param PhoneInputType|null $proxyPhone
     */
    public function setProxyPhone(?PhoneInputType $proxyPhone)
    {
        $this->proxyPhone = $proxyPhone;
    }
}
