<?php

declare(strict_types=1);

namespace Component\Import\Integrity;

use DateTime;
use Entity\CountrySpecific;
use Exception;
use Utils\HouseholdExportCSVService;
use Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use Component\Import\CellError\CellError;
use Component\Import\CellParameters;
use Component\Import\Utils\ImportDateConverter;
use Enum\EnumTrait;
use Validator\Constraints\EmptyCountrySpecifics;
use Validator\Constraints\ImportDate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints\Enum;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Validator\Constraints\CountrySpecificDataType;

class ImportLine
{
    /**
     * Not an user input property. Its purpose is only to display information about the import process.
     */
    public $humansisData;

    /**
     * Not an user input property. Its purpose is only to display information about the import process.
     */
    public $humansisComment;

    #[Assert\Type('scalar', groups: ['household', 'member'])]
    public $addressStreet;

    #[Assert\Type('scalar', groups: ['household', 'member'])]
    public $addressNumber;

    #[Assert\Type('scalar', groups: ['household', 'member'])]
    public $addressPostcode;

    #[Assert\Type(type: ['string', 'numeric'], groups: ['household', 'member'])]
    public $campName;

    #[Assert\Type('numeric', groups: ['household', 'member'])]
    public $tentNumber;

    /**
     * @Enum(enumClass="Enum\Livelihood", groups={"household", "member"})
     */
    public $livelihood;

    #[Assert\Type('integer', groups: ['household', 'member'])]
    #[Assert\GreaterThanOrEqual(0)]
    public $income;

    #[Assert\Type('numeric', groups: ['household', 'member'])]
    public $foodConsumptionScore;

    #[Assert\Type('numeric', groups: ['household', 'member'])]
    public $copingStrategiesIndex;

    #[Assert\Type('string', groups: ['household', 'member'])]
    public $notes;

    #[Assert\Type('string', groups: ['household', 'member'])]
    public $enumeratorName;

    #[Assert\Type('float', groups: ['household', 'member'])]
    public $latitude;

    #[Assert\Type('float', groups: ['household', 'member'])]
    public $longitude;

    #[Assert\Type('string', groups: ['household', 'member'])]
    #[Assert\NotBlank(groups: ['household'])]
    public $adm1;

    #[Assert\Type('string', groups: ['household', 'member'])]
    public $adm2;

    #[Assert\Type('string', groups: ['household', 'member'])]
    public $adm3;

    #[Assert\Type('string', groups: ['household', 'member'])]
    public $adm4;

    #[Assert\Type('string', groups: ['household', 'member'])]
    #[Assert\NotBlank(groups: ['household', 'member'])]
    public $localGivenName;

    #[Assert\Type('string', groups: ['household', 'member'])]
    #[Assert\NotBlank(groups: ['household', 'member'])]
    public $localFamilyName;

    #[Assert\Type('string', groups: ['household', 'member'])]
    public $localParentsName;

    #[Assert\Type('string', groups: ['household', 'member'])]
    public $englishGivenName;

    #[Assert\Type('string', groups: ['household', 'member'])]
    public $englishFamilyName;

    #[Assert\Type('string', groups: ['household', 'member'])]
    public $englishParentsName;

    /**
     * @Enum(enumClass="Enum\PersonGender", groups={"household", "member"})
     */
    #[Assert\NotNull(groups: ['household', 'member'])]
    public $gender;

    /**
     * @Enum(enumClass="Enum\HouseholdHead", groups={"household", "member"})
     */
    #[Assert\NotNull(groups: ['household', 'member'])]
    public $head;

    /**
     * @Enum(enumClass="Enum\ResidencyStatus", groups={"household", "member"})
     */
    #[Assert\NotNull(groups: ['household', 'member'])]
    public $residencyStatus;

    /**
     * @ImportDate(groups={"household", "member"})
     */
    #[Assert\NotBlank(groups: ['household', 'member'])]
    public $dateOfBirth;

    /**
     * @Enum(enumClass="Enum\VulnerabilityCriteria", array=true, groups={"household", "member"})
     */
    #[Assert\Type('string', groups: ['household', 'member'])]
    public $vulnerabilityCriteria;

    /**
     * @Enum(enumClass="Enum\PhoneTypes", groups={"household", "member"})
     */
    public $typePhone1;

    #[Assert\Type('scalar', groups: ['household', 'member'])]
    public $prefixPhone1;

    #[Assert\Type('numeric', groups: ['household', 'member'])]
    public $numberPhone1;

    /**
     * @Enum(enumClass="Enum\VariableBool", groups={"household", "member"})
     */
    public $proxyPhone1;

    /**
     * @Enum(enumClass="Enum\PhoneTypes", groups={"household", "member"})
     */
    public $typePhone2;

    #[Assert\Type('scalar', groups: ['household', 'member'])]
    public $prefixPhone2;

    #[Assert\Type('numeric', groups: ['household', 'member'])]
    public $numberPhone2;

    /**
     * @Enum(enumClass="Enum\VariableBool", groups={"household", "member"})
     */
    public $proxyPhone2;

    /**
     * @Enum(enumClass="Enum\NationalIdType", groups={"household", "member"})
     */
    public $primaryIdType;

    #[Assert\Type('scalar', groups: ['household', 'member'])]
    public $primaryIdNumber;

    /**
     * @Enum(enumClass="Enum\NationalIdType", groups={"household", "member"})
     */
    public $secondaryIdType;

    #[Assert\Type('scalar', groups: ['household', 'member'])]
    public $secondaryIdNumber;

    /**
     * @Enum(enumClass="Enum\NationalIdType", groups={"household", "member"})
     */
    public $tertiaryIdType;

    #[Assert\Type('scalar', groups: ['household', 'member'])]
    public $tertiaryIdNumber;

    /**
     * @Enum(enumClass="Enum\HouseholdShelterStatus", groups={"household", "member"})
     */
    public $shelterStatus;

    /**
     * @Enum(enumClass="Enum\HouseholdAssets", array=true, groups={"household", "member"})
     */
    #[Assert\Type('string', groups: ['household', 'member'])]
    public $assets;

    #[Assert\Type('numeric', groups: ['household', 'member'])]
    public $debtLevel;

    /**
     * @Enum(enumClass="Enum\HouseholdSupportReceivedType", array=true, groups={"household", "member"})
     */
    #[Assert\Type('string', groups: ['household', 'member'])]
    public $supportReceivedTypes;

    /**
     * @ImportDate(groups={"household", "member"}),
     */
    public $supportDateReceived;

    #[Assert\IsNull(groups: ['member'])]
    #[Assert\Type(type: ['integer', null], groups: ['household'])]
    #[Assert\GreaterThanOrEqual(value: 0, groups: ['household'])]
    public $f0;

    #[Assert\IsNull(groups: ['member'])]
    #[Assert\Type(type: ['integer', null], groups: ['household'])]
    #[Assert\GreaterThanOrEqual(value: 0, groups: ['household'])]
    public $f2;

    #[Assert\IsNull(groups: ['member'])]
    #[Assert\Type(type: ['integer', null], groups: ['household'])]
    #[Assert\GreaterThanOrEqual(value: 0, groups: ['household'])]
    public $f6;

    #[Assert\IsNull(groups: ['member'])]
    #[Assert\Type(type: ['integer', null], groups: ['household'])]
    #[Assert\GreaterThanOrEqual(value: 0, groups: ['household'])]
    public $f18;

    #[Assert\IsNull(groups: ['member'])]
    #[Assert\Type(type: ['integer', null], groups: ['household'])]
    #[Assert\GreaterThanOrEqual(value: 0, groups: ['household'])]
    public $f60;

    #[Assert\IsNull(groups: ['member'])]
    #[Assert\Type(type: ['integer', null], groups: ['household'])]
    #[Assert\GreaterThanOrEqual(value: 0, groups: ['household'])]
    public $m0;

    #[Assert\IsNull(groups: ['member'])]
    #[Assert\Type(type: ['integer', null], groups: ['household'])]
    #[Assert\GreaterThanOrEqual(value: 0, groups: ['household'])]
    public $m2;

    #[Assert\IsNull(groups: ['member'])]
    #[Assert\Type(type: ['integer', null], groups: ['household'])]
    #[Assert\GreaterThanOrEqual(value: 0, groups: ['household'])]
    public $m6;

    #[Assert\IsNull(groups: ['member'])]
    #[Assert\Type(type: ['integer', null], groups: ['household'])]
    #[Assert\GreaterThanOrEqual(value: 0, groups: ['household'])]
    public $m18;

    #[Assert\IsNull(groups: ['member'])]
    #[Assert\Type(type: ['integer', null], groups: ['household'])]
    #[Assert\GreaterThanOrEqual(value: 0, groups: ['household'])]
    public $m60;

    /**
     * @var array countrySpecific::id => countrySpecificAnswer::answer
     * @EmptyCountrySpecifics(groups={"member"})
     * @Assert\All(
     *     constraints={
     *         @CountrySpecificDataType()
     *     },
     *     groups={"household"}
     * )
     */
    public $countrySpecifics = [];

    /** @var string[] */
    private array $excelDateTimeFormatProperties = [];

    /** @var CellError[] */
    private array $errors = [];

    public function __construct(array $content, private readonly string $countryIso3, private readonly EntityManagerInterface $entityManager)
    {
        foreach (HouseholdExportCSVService::MAPPING_PROPERTIES as $header => $property) {
            if (isset($content[$header])) {
                $value = $content[$header][CellParameters::VALUE];

                if (Date::isDateTimeFormatCode($content[$header][CellParameters::NUMBER_FORMAT])) {
                    $this->excelDateTimeFormatProperties[] = $property;
                }

                if (is_string($value)) {
                    $this->$property = preg_replace('/[\pZ\pC]/u', ' ', $value); // replace unicode spaces by ASCII ones
                    $this->$property = trim($this->$property);

                    // back retype to int if there is only numbers
                    if (ctype_digit($this->$property) && !(str_starts_with($this->$property, '0'))) {
                        $this->$property = (int) $this->$property;
                    }
                } else {
                    $this->$property = $value;
                }

                if (isset($content[$header][CellParameters::ERRORS])) {
                    $this->errors[] = new CellError($content[$header][CellParameters::ERRORS], $property, $value);
                }
            }
        }

        $countrySpecifics = $entityManager->getRepository(CountrySpecific::class)->findBy(
            ['countryIso3' => $countryIso3],
            ['id' => 'asc']
        );
        foreach ($countrySpecifics as $countrySpecific) {
            if (
                isset($content[$countrySpecific->getFieldString()]) && $content[$countrySpecific->getFieldString(
                )][CellParameters::DATA_TYPE] !== DataType::TYPE_NULL
            ) {
                $this->countrySpecifics[$countrySpecific->getId()] = [
                    'countrySpecific' => $countrySpecific,
                    'value' => $content[$countrySpecific->getFieldString()][CellParameters::VALUE],
                ];
            }
        }
    }

    #[Assert\Callback(groups: ['household', 'member'])]
    public function violateCellErrors(ExecutionContextInterface $context): void
    {
        foreach ($this->errors as $error) {
            $context->buildViolation($error->getType())
                ->atPath($error->getProperty())
                ->setInvalidValue($error->getValue())
                ->addViolation();
        }
    }

    #[Assert\IsTrue(message: 'Prefix should not be blank if phone number is filled', payload: ['propertyPath' => 'prefixPhone1'], groups: ['household', 'member'])]
    public function isPrefixPhone1Valid(): bool
    {
        return !$this->numberPhone1 || $this->prefixPhone1;
    }

    #[Assert\IsTrue(message: 'Prefix should not be blank if phone number is filled', payload: ['propertyPath' => 'prefixPhone2'], groups: ['household', 'member'])]
    public function isPrefixPhone2Valid(): bool
    {
        return !$this->numberPhone2 || $this->prefixPhone2;
    }

    #[Assert\IsTrue(message: 'Camp must have defined both Tent number and Camp name', payload: ['propertyPath' => 'campName'], groups: ['household', 'member'])]
    public function isCampValidOrEmpty(): bool
    {
        return $this->isCampValid()
            xor ($this->isEmpty($this->tentNumber) && $this->isEmpty($this->campName));
    }

    public function isCampValid(): bool
    {
        return (!$this->isEmpty($this->tentNumber) && !$this->isEmpty($this->campName));
    }

    #[Assert\IsTrue(message: 'Address must have defined street, number and postcode', payload: ['propertyPath' => 'addressStreet'], groups: ['household', 'member'])]
    public function isAddressValidOrEmpty(): bool
    {
        return $this->isAddressValid()
            xor ($this->isEmpty($this->addressNumber)) && $this->isEmpty($this->addressPostcode) && $this->isEmpty(
                $this->addressStreet
            );
    }

    private function isAddressValid(): bool
    {
        return (!$this->isEmpty($this->addressNumber))
            && !$this->isEmpty($this->addressPostcode)
            && !$this->isEmpty($this->addressStreet);
    }

    private function isEmpty($value)
    {
        return "" === trim((string) $value);
    }

    #[Assert\IsTrue(message: 'Camp or address must be fully defined', payload: ['propertyPath' => 'addressStreet'], groups: ['household'])]
    public function isAddressExists(): bool
    {
        return $this->isAddressValid() || $this->isCampValid();
    }

    #[Assert\IsFalse(message: 'Address or Camp must be defined, not both', payload: ['propertyPath' => 'addressStreet'], groups: ['household'])]
    public function isFilledAddressOrCamp(): bool
    {
        $isCompleteAddress = !empty($this->addressNumber) && !empty($this->addressPostcode) && !empty($this->addressStreet);
        $isCompleteCamp = !empty($this->campName) && !empty($this->tentNumber);

        return $isCompleteAddress && $isCompleteCamp;
    }

    #[Assert\IsTrue(message: 'There is no Adm1 like this', payload: ['propertyPath' => 'adm1'], groups: ['household', 'member'])]
    public function isValidAdm1(): bool
    {
        if (!$this->adm1) {
            return true;
        }

        $locationsArray = [EnumTrait::normalizeValue($this->adm1)];

        $location = $this->entityManager->getRepository(Location::class)->getByNormalizedNames(
            $this->countryIso3,
            $locationsArray
        );

        return null !== $location;
    }

    #[Assert\IsTrue(message: 'There is no Adm2 in this location', payload: ['propertyPath' => 'adm2'], groups: ['household', 'member'])]
    public function isValidAdm2(): bool
    {
        if (!$this->adm2) {
            return true;
        }

        $locationsArray = [EnumTrait::normalizeValue($this->adm1), EnumTrait::normalizeValue($this->adm2)];

        $location = $this->entityManager->getRepository(Location::class)->getByNormalizedNames(
            $this->countryIso3,
            $locationsArray
        );

        return null !== $location;
    }

    #[Assert\IsTrue(message: 'There is no Adm3 in this location', payload: ['propertyPath' => 'adm3'], groups: ['household', 'member'])]
    public function isValidAdm3(): bool
    {
        if (!$this->adm3) {
            return true;
        }

        $locationsArray = [
            EnumTrait::normalizeValue($this->adm1),
            EnumTrait::normalizeValue($this->adm2),
            EnumTrait::normalizeValue($this->adm3),
        ];

        $location = $this->entityManager->getRepository(Location::class)->getByNormalizedNames(
            $this->countryIso3,
            $locationsArray
        );

        return null !== $location;
    }

    #[Assert\IsTrue(message: 'There is no Adm4 in this location', payload: ['propertyPath' => 'adm4'], groups: ['household', 'member'])]
    public function isValidAdm4(): bool
    {
        if (!$this->adm4) {
            return true;
        }

        $locationsArray = [
            EnumTrait::normalizeValue($this->adm1),
            EnumTrait::normalizeValue($this->adm2),
            EnumTrait::normalizeValue($this->adm3),
            EnumTrait::normalizeValue($this->adm4),
        ];

        $location = $this->entityManager->getRepository(Location::class)->getByNormalizedNames(
            $this->countryIso3,
            $locationsArray
        );

        return null !== $location;
    }

    #[Assert\IsTrue(message: 'When ID Number is filled, ID type has to be filled too.', payload: ['propertyPath' => 'primaryIdType'], groups: ['household', 'member'])]
    public function isPrimaryIdTypeCorrectlyFilled(): bool
    {
        if (empty($this->primaryIdNumber)) {
            return true;
        }

        return !empty($this->primaryIdType);
    }

    #[Assert\IsTrue(message: 'When ID Type is filled, ID number has to be filled too.', payload: ['propertyPath' => 'primaryIdNumber'], groups: ['household', 'member'])]
    public function isIdNumberCorrectlyFilled(): bool
    {
        if (empty($this->primaryIdType)) {
            return true;
        }

        return !empty($this->primaryIdNumber);
    }

    #[Assert\IsTrue(message: 'When ID Number is filled, ID type has to be filled too.', payload: ['propertyPath' => 'secondaryIdType'], groups: ['household', 'member'])]
    public function isSecondaryIdTypeCorrectlyFilled(): bool
    {
        if (empty($this->secondaryIdNumber)) {
            return true;
        }

        return !empty($this->secondaryIdType);
    }

    #[Assert\IsTrue(message: 'Has to be different then Primary ID type.', payload: ['propertyPath' => 'secondaryIdType'], groups: ['household', 'member'])]
    public function isSecondaryIdTypeDuplicity(): bool
    {
        if (empty($this->secondaryIdType)) {
            return true;
        }

        return $this->primaryIdType !== $this->secondaryIdType;
    }

    #[Assert\IsTrue(message: 'When ID Type is filled, ID number has to be filled too.', payload: ['propertyPath' => 'secondaryIdNumber'], groups: ['household', 'member'])]
    public function isSecondaryIdNumberCorrectlyFilled(): bool
    {
        if (empty($this->secondaryIdType)) {
            return true;
        }

        return !empty($this->secondaryIdNumber);
    }

    #[Assert\IsTrue(message: 'Primary ID has to be filled before Secondary ID.', payload: ['propertyPath' => 'primaryIdNumber'], groups: ['household', 'member'])]
    public function isPrimaryIdFilledWithSecondaryId(): bool
    {
        if (empty($this->secondaryIdNumber)) {
            return true;
        }
        return !empty($this->primaryIdNumber);
    }

    #[Assert\IsTrue(message: 'When ID Number is filled, ID type has to be filled too.', payload: ['propertyPath' => 'tertiaryIdType'], groups: ['household', 'member'])]
    public function isTertiaryIdTypeCorrectlyFilled(): bool
    {
        if (empty($this->tertiaryIdNumber)) {
            return true;
        }

        return !empty($this->tertiaryIdType);
    }

    #[Assert\IsTrue(message: 'Has to be different then Primary ID type or Secondary type.', payload: ['propertyPath' => 'secondaryIdType'], groups: ['household', 'member'])]
    public function isTertiaryIdTypeDuplicity(): bool
    {
        if (empty($this->tertiaryIdType)) {
            return true;
        }

        return $this->primaryIdType !== $this->tertiaryIdType && $this->secondaryIdType !== $this->tertiaryIdType;
    }

    #[Assert\IsTrue(message: 'When ID Type is filled, ID number has to be filled too.', payload: ['propertyPath' => 'tertiaryIdNumber'], groups: ['household', 'member'])]
    public function isTertiaryIdNumberCorrectlyFilled(): bool
    {
        if (empty($this->tertiaryIdType)) {
            return true;
        }

        return !empty($this->tertiaryIdNumber);
    }

    #[Assert\IsTrue(message: 'Secondary ID has to be filled before Tertiary ID.', payload: ['propertyPath' => 'secondaryIdNumber'], groups: ['household', 'member'])]
    public function isSecondaryIdFilledWithTertiaryId(): bool
    {
        if (empty($this->tertiaryIdNumber)) {
            return true;
        }
        return !empty($this->secondaryIdNumber);
    }

    /**
     * @throws Exception
     */
    #[Assert\IsTrue(message: 'Date is not valid. Use Excel Date format or string in format DD-MM-YYYY.', payload: ['propertyPath' => 'dateOfBirth'], groups: ['household', 'member'])]
    public function isDateOfBirthValid(): bool
    {
        if (null === $this->dateOfBirth) {
            return true;
        }

        if (is_int($this->dateOfBirth) || is_float($this->dateOfBirth)) {
            return in_array('dateOfBirth', $this->excelDateTimeFormatProperties);
        }

        return true;
    }

    #[Assert\IsTrue(message: 'Date is not valid. Use Excel Date format or string in format DD-MM-YYYY.', payload: ['propertyPath' => 'supportDateReceived'], groups: ['household', 'member'])]
    public function isSupportDateReceivedValid(): bool
    {
        if (null === $this->supportDateReceived) {
            return true;
        }

        if (is_int($this->supportDateReceived) || is_float($this->supportDateReceived)) {
            return in_array('supportDateReceived', $this->excelDateTimeFormatProperties);
        }

        return true;
    }

    public function getDateOfBirth(): DateTime
    {
        return ImportDateConverter::toDatetime($this->dateOfBirth);
    }

    public function getSupportDateReceived(): DateTime
    {
        return ImportDateConverter::toDatetime($this->supportDateReceived);
    }

    public function hasPrimaryId(): bool
    {
        return $this->hasId(0);
    }

    public function hasSecondaryId(): bool
    {
        return $this->hasId(1);
    }

    public function hasTertiaryId(): bool
    {
        return $this->hasId(2);
    }

    public function hasId(int $index): bool
    {
        $ids = $this->getIds();
        $id = $ids[$index];
        if ($index >= count($ids)) {
            return false;
        }

        return isset($id['type']) && isset($id['number']);
    }

    /**
     * @return array[]
     */
    public function getIds(): array
    {
        return [
            [
                'type' => $this->primaryIdType,
                'number' => $this->primaryIdNumber,
            ],
            [
                'type' => $this->secondaryIdType,
                'number' => $this->secondaryIdNumber,
            ],
            [
                'type' => $this->tertiaryIdType,
                'number' => $this->tertiaryIdNumber,
            ],
        ];
    }

    public function getFilledIds(): array
    {
        $ids = $this->getIds();
        $filledIds = [];
        for ($i = 0; $i < count($ids); $i++) {
            if ($this->hasId($i)) {
                $filledIds[] = $ids[$i];
            }
        }

        return $filledIds;
    }
}
