<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Integrity;

use BeneficiaryBundle\Utils\HouseholdExportCSVService;
use CommonBundle\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\CellParameters;
use NewApiBundle\Enum\EnumTrait;
use NewApiBundle\Validator\Constraints\ImportDate;
use Symfony\Component\Validator\Constraints as Assert;
use NewApiBundle\Validator\Constraints\Enum;

class HouseholdMember
{
    use EnumNormalizeTrait;
    use HouseholdInputBuilderTrait;

    /**
     * @Assert\Type("scalar")
     */
    protected $addressStreet;

    /**
     * @Assert\Type("scalar")
     */
    protected $addressNumber;

    /**
     * @Assert\Type("scalar")
     */
    protected $addressPostcode;

    /**
     * @Assert\Type("string")
     */
    protected $campName;

    /**
     * @Assert\Type("numeric")
     */
    protected $tentNumber;

    /**
     * @Enum(enumClass="ProjectBundle\Enum\Livelihood")
     */
    protected $livelihood;

    /**
     * @Assert\Type("numeric"),
     * @Assert\Range(min = 1, max = 5),
     */
    protected $incomeLevel;

    /**
     * @Assert\Type("numeric")
     */
    protected $foodConsumptionScore;

    /**
     * @Assert\Type("numeric")
     */
    protected $copingStrategiesIndex;

    /**
     * @Assert\Type("string")
     */
    protected $notes;

    /**
     * @Assert\Type("string")
     */
    protected $enumeratorName;

    /**
     * @Assert\Type("float")
     */
    protected $latitude;

    /**
     * @Assert\Type("float")
     */
    protected $longitude;

    /**
     * @Assert\Type("string")
     */
    protected $adm1;

    /**
     * @Assert\Type("string")
     */
    protected $adm2;

    /**
     * @Assert\Type("string")
     */
    protected $adm3;

    /**
     * @Assert\Type("string")
     */
    protected $adm4;

    /**
     * @Assert\Type("string")
     * @Assert\NotBlank()
     */
    protected $localGivenName;

    /**
     * @Assert\Type("string")
     * @Assert\NotBlank()
     */
    protected $localFamilyName;

    /**
     * @Assert\Type("string")
     */
    protected $localParentsName;

    /**
     * @Assert\Type("string")
     */
    protected $englishGivenName;

    /**
     * @Assert\Type("string")
     */
    protected $englishFamilyName;

    /**
     * @Assert\Type("string")
     */
    protected $englishParentsName;

    /**
     * @Enum(enumClass="NewApiBundle\Enum\PersonGender")
     */
    protected $gender;

    /**
     * @Assert\NotNull()
     * @Enum(enumClass="NewApiBundle\Enum\HouseholdHead")
     */
    protected $head;

    /**
     * @Assert\NotNull()
     * @Enum(enumClass="BeneficiaryBundle\Enum\ResidencyStatus")
     */
    protected $residencyStatus;

    /**
     * @ImportDate(),
     * @Assert\NotBlank(),
     */
    protected $dateOfBirth;

    /**
     * @Assert\Type("string")
     * @Enum(enumClass="NewApiBundle\Enum\VulnerabilityCriteria", array=true)
     */
    protected $vulnerabilityCriteria;

    /**
     * @Enum(enumClass="NewApiBundle\Enum\PhoneTypes")
     */
    protected $typePhone1;

    /**
     * @Assert\Type("string")
     */
    protected $prefixPhone1;

    /**
     * @Assert\Type("numeric")
     */
    protected $numberPhone1;

    /**
     * @Enum(enumClass="NewApiBundle\Enum\VariableBool")
     */
    protected $proxyPhone1;

    /**
     * @Enum(enumClass="NewApiBundle\Enum\PhoneTypes")
     */
    protected $typePhone2;

    /**
     * @Assert\Type("string")
     */
    protected $prefixPhone2;

    /**
     * @Assert\Type("numeric")
     */
    protected $numberPhone2;

    /**
     * @Enum(enumClass="NewApiBundle\Enum\VariableBool")
     */
    protected $proxyPhone2;

    /**
     * @Enum(enumClass="NewApiBundle\Enum\NationalIdType")
     */
    protected $idType;

    /**
     * @Assert\Type("scalar")
     */
    protected $idNumber;

    /**
     * @Enum(enumClass="NewApiBundle\Enum\HouseholdShelterStatus")
     */
    protected $shelterStatus;

    /**
     * @Assert\Type("string")
     * @Enum(enumClass="NewApiBundle\Enum\HouseholdAssets", array=true)
     */
    protected $assets;

    /**
     * @Assert\Type("numeric")
     */
    protected $debtLevel;

    /**
     * @Assert\Type("string")
     * @Enum(enumClass="NewApiBundle\Enum\HouseholdSupportReceivedType", array=true)
     */
    protected $supportReceivedTypes;

    /**
     * @ImportDate(),
     */
    protected $supportDateReceived;

    /**
     * @Assert\IsNull(),
     */
    protected $f0;

    /**
     * @Assert\IsNull(),
     */
    protected $f2;

    /**
     * @Assert\IsNull(),
     */
    protected $f6;

    /**
     * @Assert\IsNull(),
     */
    protected $f18;

    /**
     * @Assert\IsNull(),
     */
    protected $f60;

    /**
     * @Assert\IsNull(),
     */
    protected $m0;

    /**
     * @Assert\IsNull(),
     */
    protected $m2;

    /**
     * @Assert\IsNull(),
     */
    protected $m6;

    /**
     * @Assert\IsNull(),
     */
    protected $m18;

    /**
     * @Assert\IsNull(),
     */
    protected $m60;

    /** @var string */
    private $countryIso3;

    /**
     * @Assert\Count(max="0")
     */
    protected $countrySpecifics = [];

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(array $content, string $countryIso3, EntityManagerInterface $entityManager)
    {
        $this->countryIso3 = $countryIso3;
        $this->entityManager = $entityManager;

        foreach (HouseholdExportCSVService::MAPPING_PROPERTIES as $header => $property) {
            if (isset($content[$header])) {
                $value = $content[$header][CellParameters::VALUE];
                if (is_string($value)) {
                    $this->$property = preg_replace('/[\pZ\pC]/u', ' ', (string)$value); // replace unicode spaces by ASCII ones
                    $this->$property = trim($this->$property);

                    // back retype to int if there is only numbers
                    if (ctype_digit($this->$property)) {
                        $this->$property = (int) $this->$property;
                    }
                } else {
                    $this->$property = $value;
                }
            }
        }
    }

    /**
     * @Assert\IsTrue(message="There is no Adm1 like this", payload={"propertyPath"="adm1"})
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
     * @Assert\IsTrue(message="There is no Adm2 in this location", payload={"propertyPath"="adm2"})
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
     * @Assert\IsTrue(message="There is no Adm3 in this location", payload={"propertyPath"="adm3"})
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
     * @Assert\IsTrue(message="There is no Adm4 in this location", payload={"propertyPath"="adm4"})
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
}
