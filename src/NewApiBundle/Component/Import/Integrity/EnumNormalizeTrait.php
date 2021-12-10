<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Integrity;

use BeneficiaryBundle\Enum\ResidencyStatus;
use NewApiBundle\Component\Import\Utils\ImportDateConverter;
use NewApiBundle\Enum\HouseholdAssets;
use NewApiBundle\Enum\HouseholdShelterStatus;
use NewApiBundle\Enum\HouseholdSupportReceivedType;
use NewApiBundle\Enum\NationalIdType;
use NewApiBundle\Enum\PersonGender;
use NewApiBundle\Enum\PhoneTypes;
use NewApiBundle\Enum\VariableBool;
use NewApiBundle\InputType\Helper\EnumsBuilder;
use ProjectBundle\Enum\Livelihood;
use Symfony\Component\Validator\Constraints as Assert;

trait EnumNormalizeTrait
{
    public function isHead(): bool
    {
        return VariableBool::valueFromAPI($this->head);
    }

    /**
     * @Assert\Choice(callback={"\NewApiBundle\Enum\PersonGender", "values"}, strict=true)
     * @return string
     */
    public function getGender(): string
    {
        return PersonGender::valueFromAPI($this->gender);
    }

    /**
     * @Assert\Choice(callback={"\BeneficiaryBundle\Enum\ResidencyStatus", "values"}, strict=true)
     * @return string
     */
    public function getResidencyStatus(): ?string
    {
        if (empty($this->residencyStatus)) return null;
        return ResidencyStatus::valueFromAPI($this->residencyStatus);
    }

    /**
     * @Assert\Choice(callback={"\NewApiBundle\Enum\HouseholdShelterStatus", "values"}, strict=true)
     * @return string
     */
    public function getShelterStatus(): ?string
    {
        if (empty($this->shelterStatus)) return null;
        return HouseholdShelterStatus::valueFromAPI($this->shelterStatus);
    }

    /**
     * @Assert\Choice(callback={"\ProjectBundle\Enum\Livelihood", "values"}, strict=true)
     * @return string|null
     * @throws \NewApiBundle\Enum\EnumValueNoFoundException
     */
    public function getLivelihood(): ?string
    {
        return $this->livelihood ? Livelihood::valueFromAPI($this->livelihood) : null;
    }

    /**
     * @Assert\Choice(callback={"\NewApiBundle\Enum\NationalIdType", "values"}, strict=true)
     * @return string|null
     * @throws \NewApiBundle\Enum\EnumValueNoFoundException
     */
    public function getIdType(): ?string
    {
        return $this->idType ? NationalIdType::valueFromAPI($this->idType) : null;
    }

    /**
     * @Assert\Choice(callback={"\NewApiBundle\Enum\PhoneTypes", "values"}, strict=true)
     * @return string
     */
    public function getTypePhone1(): ?string
    {
        if (empty($this->typePhone1)) return null;
        return PhoneTypes::valueFromAPI($this->typePhone1);
    }

    /**
     * @return bool|null
     */
    public function isProxyPhone1(): bool
    {
        if (empty($this->proxyPhone1)) return false;
        return VariableBool::valueFromAPI($this->proxyPhone1);
    }

    /**
     * @Assert\Choice(callback={"\NewApiBundle\Enum\PhoneTypes", "values"}, strict=true)
     * @return string
     */
    public function getTypePhone2(): ?string
    {
        if (empty($this->typePhone2)) return null;
        return PhoneTypes::valueFromAPI($this->typePhone2);
    }

    /**
     * @return bool|null
     */
    public function isProxyPhone2(): bool
    {
        if (empty($this->proxyPhone2)) return false;
        return VariableBool::valueFromAPI($this->proxyPhone2);
    }

    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Choice(callback={"\NewApiBundle\Enum\HouseholdAssets", "values"}, strict=true)
     *     },
     *     groups={"Strict"}
     * )
     * @return array
     */
    public function getAssets(): array
    {
        $enumBuilder = new EnumsBuilder(HouseholdAssets::class);
        $enumBuilder->setNullToEmptyArrayTransformation();
        return $enumBuilder->buildInputValuesFromExplode($this->assets);
    }

    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Choice(callback={"\NewApiBundle\Enum\HouseholdSupportReceivedType", "values"}, strict=true)
     *     },
     *     groups={"Strict"}
     * )
     * @return array
     */
    public function getSupportReceivedTypes(): array
    {
        $enumBuilder = new EnumsBuilder(HouseholdSupportReceivedType::class);
        $enumBuilder->setNullToEmptyArrayTransformation();
        return $enumBuilder->buildInputValuesFromExplode($this->supportReceivedTypes);
    }

    public function getBirthDate(): \DateTimeInterface
    {
        return ImportDateConverter::toDatetime($this->dateOfBirth);
    }
}
