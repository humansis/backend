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
        return \NewApiBundle\Enum\HouseholdHead::valueFromAPI($this->head);
    }

    public function getGender(): string
    {
        return PersonGender::valueFromAPI($this->gender);
    }

    public function getResidencyStatus(): string
    {
        return ResidencyStatus::valueFromAPI($this->residencyStatus);
    }

    public function getShelterStatus(): ?string
    {
        return $this->shelterStatus ? HouseholdShelterStatus::valueFromAPI($this->shelterStatus) : null;
    }

    public function getLivelihood(): ?string
    {
        return $this->livelihood ? Livelihood::valueFromAPI($this->livelihood) : null;
    }

    public function getIdType(): ?string
    {
        return $this->idType ? NationalIdType::valueFromAPI($this->idType) : null;
    }

    public function getTypePhone1(): ?string
    {
        return $this->typePhone1 ? PhoneTypes::valueFromAPI($this->typePhone1) : null;
    }

    public function isProxyPhone1(): bool
    {
        return $this->proxyPhone1 && VariableBool::valueFromAPI($this->proxyPhone1);
    }

    public function getTypePhone2(): ?string
    {
        return $this->typePhone2 ? PhoneTypes::valueFromAPI($this->typePhone2) : null;
    }

    public function isProxyPhone2(): bool
    {
        return $this->proxyPhone2 && VariableBool::valueFromAPI($this->proxyPhone2);
    }

    /**
     * @Assert\All(
     *     constraints={
     *         @Enum(enumClass="NewApiBundle\Enum\HouseholdAssets")
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
     *         @Enum(enumClass="NewApiBundle\Enum\HouseholdSupportReceivedType")
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

    public function getDateOfBirth(): \DateTimeInterface
    {
        return ImportDateConverter::toDatetime($this->dateOfBirth);
    }
}
