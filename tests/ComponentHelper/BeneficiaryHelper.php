<?php

declare(strict_types=1);

namespace Tests\ComponentHelper;

use DateTime;
use Entity\Household;
use Enum\NationalIdType;
use Enum\PhoneTypes;
use Enum\ResidencyStatus;
use Exception;
use InputType\Beneficiary\Address\ResidenceAddressInputType;
use InputType\Beneficiary\BeneficiaryInputType;
use InputType\Beneficiary\NationalIdCardInputType;
use InputType\Beneficiary\PhoneInputType;
use InputType\HouseholdCreateInputType;
use Utils\HouseholdService;
use Utils\ValueGenerator\ValueGenerator;

trait BeneficiaryHelper
{
    /**
     * @throws Exception
     */
    public function createHousehold(
        HouseholdCreateInputType $householdCreateInputType,
        string $iso3,
        HouseholdService $householdService,
    ): Household {
        return $householdService->create($householdCreateInputType, $iso3);
    }

    public static function buildBeneficiaryInputType(
        bool $isHead,
        int $gender,
        ?NationalIdCardInputType $nationalIdCardInputType = null,
        ?PhoneInputType $phoneInputType = null
    ): BeneficiaryInputType {
        $bnfInputType = new BeneficiaryInputType();
        $bnfInputType->setDateOfBirth((new DateTime())->modify('-20 years')->format('Y-m-d'));
        $bnfInputType->setLocalFamilyName('Local Family ' . ValueGenerator::int(1, 1000));
        $bnfInputType->setLocalGivenName('Local Given ' . ValueGenerator::int(1, 1000));
        $bnfInputType->setLocalParentsName('Local Parents ' . ValueGenerator::int(1, 1000));
        $bnfInputType->setEnFamilyName('EN Family ' . ValueGenerator::int(1, 1000));
        $bnfInputType->setEnGivenName('EN Given ' . ValueGenerator::int(1, 1000));
        $bnfInputType->setGender($gender);

        if ($nationalIdCardInputType) {
            $bnfInputType->addNationalIdCard($nationalIdCardInputType);
        }
        if ($phoneInputType) {
            $bnfInputType->addPhone($phoneInputType);
        }
        $bnfInputType->setResidencyStatus(ResidencyStatus::RESIDENT);
        $bnfInputType->setIsHead($isHead);

        return $bnfInputType;
    }

    public static function generateNationalId(): NationalIdCardInputType
    {
        return NationalIdCardInputType::create(
            ValueGenerator::fromEnum(NationalIdType::class),
            ValueGenerator::string(10)
        );
    }

    public static function generatePhoneInputType(): PhoneInputType
    {
        return PhoneInputType::create(
            (string) ValueGenerator::int(400, 500),
            (string) ValueGenerator::int(100_000_000, 999_999_999),
            ValueGenerator::fromEnum(PhoneTypes::class)
        );
    }

    /**
     * @param BeneficiaryInputType[]|null $beneficiaryInputTypes
     */
    public static function buildHouseholdInputType(
        array $projectIds,
        ResidenceAddressInputType $residenceAddressInputType,
        ?array $beneficiaryInputTypes = null
    ): HouseholdCreateInputType {
        $inputType = new HouseholdCreateInputType();
        $inputType->setProjectIds($projectIds);
        $inputType->setDebtLevel(ValueGenerator::int(0, 5));
        $inputType->setIncomeSpentOnFood(ValueGenerator::int(0, 5));
        $inputType->setHouseIncome(ValueGenerator::int(0, 5));
        $inputType->setResidenceAddress($residenceAddressInputType);
        if ($beneficiaryInputTypes) {
            foreach ($beneficiaryInputTypes as $beneficiaryInputType) {
                $inputType->addBeneficiary($beneficiaryInputType);
            }
        }

        return $inputType;
    }

    public static function buildResidencyAddressInputType(int $locationId): ResidenceAddressInputType
    {
        $residencyInputType = new ResidenceAddressInputType();
        $residencyInputType->setLocationId($locationId);
        $residencyInputType->setNumber((string) ValueGenerator::int(1, 1000));
        $residencyInputType->setPostcode((string) ValueGenerator::int(1000, 3000));
        $residencyInputType->setStreet('Street Residency ' . ValueGenerator::int(1, 100));

        return $residencyInputType;
    }
}
