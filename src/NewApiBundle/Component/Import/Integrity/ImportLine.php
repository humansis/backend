<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Integrity;

use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Utils\HouseholdExportCSVService;
use CommonBundle\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\CellError\CellError;
use NewApiBundle\Component\Import\CellParameters;
use NewApiBundle\Component\Import\Utils\ImportDateConverter;
use NewApiBundle\Enum\EnumTrait;
use NewApiBundle\Validator\Constraints\EmptyCountrySpecifics;
use NewApiBundle\Validator\Constraints\ImportDate;
use Symfony\Component\Validator\Constraints as Assert;
use NewApiBundle\Validator\Constraints\Enum;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ImportLine
{
    public $humansisData;

    public $humansisComment;

    /**
     * @Assert\Type("scalar", groups={"household", "member"})
     */
    public $addressStreet;

    /**
     * @Assert\Type("scalar", groups={"household", "member"})
     */
    public $addressNumber;

    /**
     * @Assert\Type("scalar", groups={"household", "member"})
     */
    public $addressPostcode;

    /**
     * @Assert\Type("string", groups={"household", "member"})
     */
    public $campName;

    /**
     * @Assert\Type("numeric", groups={"household", "member"})
     */
    public $tentNumber;

    /**
     * @Enum(enumClass="ProjectBundle\Enum\Livelihood", groups={"household", "member"})
     */
    public $livelihood;

    /**
     * @Assert\Type("integer", groups={"household", "member"}),
     * @Assert\GreaterThanOrEqual("0")
     */
    public $income;

    /**
     * @Assert\Type("numeric", groups={"household", "member"})
     */
    public $foodConsumptionScore;

    /**
     * @Assert\Type("numeric", groups={"household", "member"})
     */
    public $copingStrategiesIndex;

    /**
     * @Assert\Type("string", groups={"household", "member"})
     */
    public $notes;

    /**
     * @Assert\Type("string", groups={"household", "member"})
     */
    public $enumeratorName;

    /**
     * @Assert\Type("float", groups={"household", "member"})
     */
    public $latitude;

    /**
     * @Assert\Type("float", groups={"household", "member"})
     */
    public $longitude;

    /**
     * @Assert\Type("string", groups={"household", "member"})
     * @Assert\NotBlank(groups={"household"})
     */
    public $adm1;

    /**
     * @Assert\Type("string", groups={"household", "member"})
     */
    public $adm2;

    /**
     * @Assert\Type("string", groups={"household", "member"})
     */
    public $adm3;

    /**
     * @Assert\Type("string", groups={"household", "member"})
     */
    public $adm4;

    /**
     * @Assert\Type("string", groups={"household", "member"})
     * @Assert\NotBlank(groups={"household", "member"})
     */
    public $localGivenName;

    /**
     * @Assert\Type("string", groups={"household", "member"})
     * @Assert\NotBlank(groups={"household", "member"})
     */
    public $localFamilyName;

    /**
     * @Assert\Type("string", groups={"household", "member"})
     */
    public $localParentsName;

    /**
     * @Assert\Type("string", groups={"household", "member"})
     */
    public $englishGivenName;

    /**
     * @Assert\Type("string", groups={"household", "member"})
     */
    public $englishFamilyName;

    /**
     * @Assert\Type("string", groups={"household", "member"})
     */
    public $englishParentsName;

    /**
     * @Assert\NotNull(groups={"household", "member"})
     * @Enum(enumClass="NewApiBundle\Enum\PersonGender", groups={"household", "member"})
     */
    public $gender;

    /**
     * @Assert\NotNull(groups={"household", "member"})
     * @Enum(enumClass="NewApiBundle\Enum\HouseholdHead", groups={"household", "member"})
     */
    public $head;

    /**
     * @Assert\NotNull(groups={"household", "member"})
     * @Enum(enumClass="BeneficiaryBundle\Enum\ResidencyStatus", groups={"household", "member"})
     */
    public $residencyStatus;

    /**
     * @ImportDate(groups={"household", "member"})
     * @Assert\NotBlank(groups={"household", "member"})
     */
    public $dateOfBirth;

    /**
     * @Assert\Type("string", groups={"household", "member"})
     * @Enum(enumClass="NewApiBundle\Enum\VulnerabilityCriteria", array=true, groups={"household", "member"})
     */
    public $vulnerabilityCriteria;

    /**
     * @Enum(enumClass="NewApiBundle\Enum\PhoneTypes", groups={"household", "member"})
     */
    public $typePhone1;

    /**
     * @Assert\Type("scalar", groups={"household", "member"})
     */
    public $prefixPhone1;

    /**
     * @Assert\Type("numeric", groups={"household", "member"})
     */
    public $numberPhone1;

    /**
     * @Enum(enumClass="NewApiBundle\Enum\VariableBool", groups={"household", "member"})
     */
    public $proxyPhone1;

    /**
     * @Enum(enumClass="NewApiBundle\Enum\PhoneTypes", groups={"household", "member"})
     */
    public $typePhone2;

    /**
     * @Assert\Type("scalar", groups={"household", "member"})
     */
    public $prefixPhone2;

    /**
     * @Assert\Type("numeric", groups={"household", "member"})
     */
    public $numberPhone2;

    /**
     * @Enum(enumClass="NewApiBundle\Enum\VariableBool", groups={"household", "member"})
     */
    public $proxyPhone2;

    /**
     * @Enum(enumClass="NewApiBundle\Enum\NationalIdType", groups={"household", "member"})
     */
    public $idType;

    /**
     * @Assert\Type("scalar", groups={"household", "member"})
     */
    public $idNumber;

    /**
     * @Enum(enumClass="NewApiBundle\Enum\HouseholdShelterStatus", groups={"household", "member"})
     */
    public $shelterStatus;

    /**
     * @Assert\Type("string", groups={"household", "member"})
     * @Enum(enumClass="NewApiBundle\Enum\HouseholdAssets", array=true, groups={"household", "member"})
     */
    public $assets;

    /**
     * @Assert\Type("numeric", groups={"household", "member"})
     */
    public $debtLevel;

    /**
     * @Assert\Type("string", groups={"household", "member"})
     * @Enum(enumClass="NewApiBundle\Enum\HouseholdSupportReceivedType", array=true, groups={"household", "member"})
     */
    public $supportReceivedTypes;

    /**
     * @ImportDate(groups={"household", "member"}),
     */
    public $supportDateReceived;

    /**
     * @Assert\IsNull(groups={"member"}),
     * @Assert\Type(type={"integer", "null"}, groups={"household"}),
     * @Assert\GreaterThanOrEqual(value=0, groups={"household"}),
     */
    public $f0;

    /**
     * @Assert\IsNull(groups={"member"}),
     * @Assert\Type(type={"integer", "null"}, groups={"household"}),
     * @Assert\GreaterThanOrEqual(value=0, groups={"household"}),
     */
    public $f2;

    /**
     * @Assert\IsNull(groups={"member"}),
     * @Assert\Type(type={"integer", "null"}, groups={"household"}),
     * @Assert\GreaterThanOrEqual(value=0, groups={"household"}),
     */
    public $f6;

    /**
     * @Assert\IsNull(groups={"member"}),
     * @Assert\Type(type={"integer", "null"}, groups={"household"}),
     * @Assert\GreaterThanOrEqual(value=0, groups={"household"}),
     */
    public $f18;

    /**
     * @Assert\IsNull(groups={"member"}),
     * @Assert\Type(type={"integer", "null"}, groups={"household"}),
     * @Assert\GreaterThanOrEqual(value=0, groups={"household"}),
     */
    public $f60;

    /**
     * @Assert\IsNull(groups={"member"}),
     * @Assert\Type(type={"integer", "null"}, groups={"household"}),
     * @Assert\GreaterThanOrEqual(value=0, groups={"household"}),
     */
    public $m0;

    /**
     * @Assert\IsNull(groups={"member"}),
     * @Assert\Type(type={"integer", "null"}, groups={"household"}),
     * @Assert\GreaterThanOrEqual(value=0, groups={"household"}),
     */
    public $m2;

    /**
     * @Assert\IsNull(groups={"member"}),
     * @Assert\Type(type={"integer", "null"}, groups={"household"}),
     * @Assert\GreaterThanOrEqual(value=0, groups={"household"}),
     */
    public $m6;

    /**
     * @Assert\IsNull(groups={"member"}),
     * @Assert\Type(type={"integer", "null"}, groups={"household"}),
     * @Assert\GreaterThanOrEqual(value=0, groups={"household"}),
     */
    public $m18;

    /**
     * @Assert\IsNull(groups={"member"}),
     * @Assert\Type(type={"integer", "null"}, groups={"household"}),
     * @Assert\GreaterThanOrEqual(value=0, groups={"household"}),
     */
    public $m60;

    /**
     * @var string[] countrySpecific::id => countrySpecificAnswer::answer
     * @EmptyCountrySpecifics(groups={"member"})
     */
    public $countrySpecifics = [];

    /** @var string */
    private $countryIso3;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var string[] */
    private $excelDateTimeFormatProperties = [];

    /** @var CellError[] */
    private $errors = [];

    public function __construct(array $content, string $countryIso3, EntityManagerInterface $entityManager)
    {
        $this->countryIso3 = $countryIso3;
        $this->entityManager = $entityManager;

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
                    if (ctype_digit($this->$property)) {
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

        $countrySpecifics = $entityManager->getRepository(CountrySpecific::class)->findBy(['countryIso3' => $countryIso3], ['id'=>'asc']);
        foreach ($countrySpecifics as $countrySpecific) {
            if (isset($content[$countrySpecific->getFieldString()])) {
                $this->countrySpecifics[$countrySpecific->getId()] = $content[$countrySpecific->getFieldString()][CellParameters::VALUE];
            }
        }
    }

    /**
     * @param ExecutionContextInterface $context
     *
     * @return void
     * @Assert\Callback(groups={"household", "member"})
     */
    public function violateCellErrors(ExecutionContextInterface $context): void
    {
        foreach ($this->errors as $error) {
            $context->buildViolation($error->getType())
                ->atPath($error->getProperty())
                ->setInvalidValue($error->getValue())
                ->addViolation();
        }
    }

    /**
     * @Assert\IsTrue(message="Prefix should not be blank if phone number is filled", payload={"propertyPath"="prefixPhone1"}, groups={"household", "member"})
     */
    public function isPrefixPhone1Valid(): bool
    {
        return !$this->numberPhone1 || $this->prefixPhone1;
    }

    /**
     * @Assert\IsTrue(message="Prefix should not be blank if phone number is filled", payload={"propertyPath"="prefixPhone2"}, groups={"household", "member"})
     */
    public function isPrefixPhone2Valid(): bool
    {
        return !$this->numberPhone2 || $this->prefixPhone2;
    }

    
    /**
     * @Assert\IsTrue(message="Camp must have defined both Tent number and Camp name", payload={"propertyPath"="campName"}, groups={"household", "member"})
     */
    public function isCampValid(): bool
    {
        return ($this->tentNumber && $this->campName) xor !($this->tentNumber || $this->campName);
    }

    /**
     * @Assert\IsTrue(message="Address must have defined street, number and postcode", payload={"propertyPath"="addressStreet"}, groups={"household", "member"})
     */
    public function isAddressValid(): bool
    {
        return ($this->addressNumber && $this->addressPostcode && $this->addressStreet) xor !($this->addressNumber || $this->addressPostcode || $this->addressStreet);
    }

    /**
     * @Assert\IsTrue(message="Camp or address must be fully defined", payload={"propertyPath"="addressStreet"}, groups={"household", "member"})
     */
    public function isAddressExists(): bool
    {
        return $this->isAddressValid() || $this->isCampValid();
    }

    /**
     * @Assert\IsFalse(message="Address or Camp must be defined, not both", payload={"propertyPath"="addressStreet"}, groups={"household", "member"})
     *
     * @return bool
     */
    public function isFilledAddressOrCamp(): bool
    {
        $isCompleteAddress = !empty($this->addressNumber) && !empty($this->addressPostcode) && !empty($this->addressStreet);
        $isCompleteCamp = !empty($this->campName) && !empty($this->tentNumber);

        return $isCompleteAddress && $isCompleteCamp;
    }

    /**
     * @Assert\IsTrue(message="There is no Adm1 like this", payload={"propertyPath"="adm1"}, groups={"household", "member"})
     */
    public function isValidAdm1(): bool
    {
        if (!$this->adm1) {
            return true;
        }

        $locationsArray = [EnumTrait::normalizeValue($this->adm1)];

        $location = $this->entityManager->getRepository(Location::class)->getByNormalizedNames($this->countryIso3, $locationsArray);
        return null !== $location;
    }

    /**
     * @Assert\IsTrue(message="There is no Adm2 in this location", payload={"propertyPath"="adm2"}, groups={"household", "member"})
     */
    public function isValidAdm2(): bool
    {
        if (!$this->adm2) {
            return true;
        }

        $locationsArray = [EnumTrait::normalizeValue($this->adm1), EnumTrait::normalizeValue($this->adm2)];

        $location = $this->entityManager->getRepository(Location::class)->getByNormalizedNames($this->countryIso3, $locationsArray);
        return null !== $location;
    }

    /**
     * @Assert\IsTrue(message="There is no Adm3 in this location", payload={"propertyPath"="adm3"}, groups={"household", "member"})
     */
    public function isValidAdm3(): bool
    {
        if (!$this->adm3) {
            return true;
        }

        $locationsArray = [EnumTrait::normalizeValue($this->adm1), EnumTrait::normalizeValue($this->adm2), EnumTrait::normalizeValue($this->adm3)];

        $location = $this->entityManager->getRepository(Location::class)->getByNormalizedNames($this->countryIso3, $locationsArray);
        return null !== $location;
    }

    /**
     * @Assert\IsTrue(message="There is no Adm4 in this location", payload={"propertyPath"="adm4"}, groups={"household", "member"})
     */
    public function isValidAdm4(): bool
    {
        if (!$this->adm4) {
            return true;
        }

        $locationsArray = [EnumTrait::normalizeValue($this->adm1), EnumTrait::normalizeValue($this->adm2), EnumTrait::normalizeValue($this->adm3), EnumTrait::normalizeValue($this->adm4)];

        $location = $this->entityManager->getRepository(Location::class)->getByNormalizedNames($this->countryIso3, $locationsArray);
        return null !== $location;
    }

    /**
     * @Assert\IsTrue(message="When ID Number is filled, ID type has to be filled to.", payload={"propertyPath"="idType"}, groups={"household", "member"})
     */
    public function isIdTypeCorrectlyFilled(): bool
    {
        if (null === $this->idNumber) {
            return true;
        }

        return (null !== $this->idType);
    }

    /**
     * @Assert\IsTrue(message="When ID Type is filled, ID number has to be filled to.", payload={"propertyPath"="idNumber"}, groups={"household", "member"})
     */
    public function isIdNumberCorrectlyFilled(): bool
    {
        if (null === $this->idType) {
            return true;
        }

        return (null !== $this->idNumber);
    }

    /**
     * @Assert\IsTrue(message="Date is not valid. Use Excel Date format or string in format DD-MM-YYYY.", payload={"propertyPath"="dateOfBirth"}, groups={"household", "member"})
     * @return bool
     * @throws \Exception
     */
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

    /**
     * @Assert\IsTrue(message="Date is not valid. Use Excel Date format or string in format DD-MM-YYYY.", payload={"propertyPath"="supportDateReceived"}, groups={"household", "member"})
     * @return bool
     */
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

    /**
     * @return \DateTime
     */
    public function getDateOfBirth(): \DateTime
    {
        return ImportDateConverter::toDatetime($this->dateOfBirth);
    }

    /**
     * @return \DateTime
     */
    public function getSupportDateReceived(): \DateTime
    {
        return ImportDateConverter::toDatetime($this->supportDateReceived);
    }
}
