<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Integrity;

use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Utils\HouseholdExportCSVService;
use CommonBundle\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\CellParameters;
use NewApiBundle\Component\Import\Utils\ImportDateConverter;
use NewApiBundle\InputType\Beneficiary\BeneficiaryInputType;
use NewApiBundle\InputType\Beneficiary\NationalIdCardInputType;
use NewApiBundle\InputType\Beneficiary\PhoneInputType;
use NewApiBundle\Validator\Constraints\ImportDate;
use Symfony\Component\Validator\Constraints as Assert;
use NewApiBundle\Validator\Constraints\Enum;

class ImportLine
{
    /**
     * @Assert\Type("scalar")
     */
    public $addressStreet;

    /**
     * @Assert\Type("scalar")
     */
    public $addressNumber;

    /**
     * @Assert\Type("scalar")
     */
    public $addressPostcode;

    /**
     * @Assert\Type("string")
     */
    public $campName;

    /**
     * @Assert\Type("numeric")
     */
    public $tentNumber;

    /**
     * @Enum(enumClass="ProjectBundle\Enum\Livelihood")
     */
    public $livelihood;

    /**
     * @Assert\Type("numeric"),
     * @Assert\Range(min = 1, max = 5),
     */
    public $incomeLevel;

    /**
     * @Assert\Type("numeric")
     */
    public $foodConsumptionScore;

    /**
     * @Assert\Type("numeric")
     */
    public $copingStrategiesIndex;

    /**
     * @Assert\Type("string")
     */
    public $notes;

    /**
     * @Assert\Type("string")
     */
    public $enumeratorName;

    /**
     * @Assert\Type("float")
     */
    public $latitude;

    /**
     * @Assert\Type("float")
     */
    public $longitude;

    /**
     * @Assert\Type("string")
     * @Assert\NotBlank(groups={"household"})
     */
    public $adm1;

    /**
     * @Assert\Type("string")
     */
    public $adm2;

    /**
     * @Assert\Type("string")
     */
    public $adm3;

    /**
     * @Assert\Type("string")
     */
    public $adm4;

    /**
     * @Assert\Type("string")
     * @Assert\NotBlank()
     */
    public $localGivenName;

    /**
     * @Assert\Type("string")
     * @Assert\NotBlank()
     */
    public $localFamilyName;

    /**
     * @Assert\Type("string")
     */
    public $localParentsName;

    /**
     * @Assert\Type("string")
     */
    public $englishGivenName;

    /**
     * @Assert\Type("string")
     */
    public $englishFamilyName;

    /**
     * @Assert\Type("string")
     */
    public $englishParentsName;

    /**
     * @Assert\NotNull()
     * @Enum(enumClass="NewApiBundle\Enum\PersonGender")
     */
    public $gender;

    /**
     * @Assert\NotNull()
     * @Enum(enumClass="NewApiBundle\Enum\HouseholdHead")
     */
    public $head;

    /**
     * @Assert\NotNull()
     * @Enum(enumClass="BeneficiaryBundle\Enum\ResidencyStatus")
     */
    public $residencyStatus;

    /**
     * @ImportDate()
     * @Assert\NotBlank()
     */
    public $dateOfBirth;

    /**
     * @Assert\Type("string")
     */
    public $vulnerabilityCriteria;

    /**
     * @Enum(enumClass="NewApiBundle\Enum\PhoneTypes")
     */
    public $typePhone1;

    /**
     * @Assert\Type("scalar")
     */
    public $prefixPhone1;

    /**
     * @Assert\Type("numeric")
     */
    public $numberPhone1;

    /**
     * @Enum(enumClass="NewApiBundle\Enum\VariableBool")
     */
    public $proxyPhone1;

    /**
     * @Enum(enumClass="NewApiBundle\Enum\PhoneTypes")
     */
    public $typePhone2;

    /**
     * @Assert\Type("string")
     */
    public $prefixPhone2;

    /**
     * @Assert\Type("numeric")
     */
    public $numberPhone2;

    /**
     * @Enum(enumClass="NewApiBundle\Enum\VariableBool")
     */
    public $proxyPhone2;

    /**
     * @Enum(enumClass="NewApiBundle\Enum\NationalIdType")
     */
    public $idType;

    /**
     * @Assert\Type("scalar")
     */
    public $idNumber;

    /**
     * @Enum(enumClass="NewApiBundle\Enum\HouseholdShelterStatus")
     */
    public $shelterStatus;

    /**
     * @Assert\Type("string")
     */
    public $assets;

    /**
     * @Assert\Type("numeric")
     */
    public $debtLevel;

    /**
     * @Assert\Type("string")
     */
    public $supportReceivedTypes;

    /**
     * @ImportDate(),
     */
    public $supportDateReceived;

    /**
     * @Assert\IsNull(groups={"member"}),
     * @Assert\Type(type="integer", groups={"household"}),
     * @Assert\GreaterThanOrEqual(value=0, groups={"household"}),
     */
    public $f0;

    /**
     * @Assert\IsNull(groups={"member"}),
     * @Assert\Type(type="integer", groups={"household"}),
     * @Assert\GreaterThanOrEqual(value=0, groups={"household"}),
     */
    public $f2;

    /**
     * @Assert\IsNull(groups={"member"}),
     * @Assert\Type(type="integer", groups={"household"}),
     * @Assert\GreaterThanOrEqual(value=0, groups={"household"}),
     */
    public $f6;

    /**
     * @Assert\IsNull(groups={"member"}),
     * @Assert\Type(type="integer", groups={"household"}),
     * @Assert\GreaterThanOrEqual(value=0, groups={"household"}),
     */
    public $f18;

    /**
     * @Assert\IsNull(groups={"member"}),
     * @Assert\Type(type="integer", groups={"household"}),
     * @Assert\GreaterThanOrEqual(value=0, groups={"household"}),
     */
    public $f60;

    /**
     * @Assert\IsNull(groups={"member"}),
     * @Assert\Type(type="integer", groups={"household"}),
     * @Assert\GreaterThanOrEqual(value=0, groups={"household"}),
     */
    public $m0;

    /**
     * @Assert\IsNull(groups={"member"}),
     * @Assert\Type(type="integer", groups={"household"}),
     * @Assert\GreaterThanOrEqual(value=0, groups={"household"}),
     */
    public $m2;

    /**
     * @Assert\IsNull(groups={"member"}),
     * @Assert\Type(type="integer", groups={"household"}),
     * @Assert\GreaterThanOrEqual(value=0, groups={"household"}),
     */
    public $m6;

    /**
     * @Assert\IsNull(groups={"member"}),
     * @Assert\Type(type="integer", groups={"household"}),
     * @Assert\GreaterThanOrEqual(value=0, groups={"household"}),
     */
    public $m18;

    /**
     * @Assert\IsNull(groups={"member"}),
     * @Assert\Type(type="integer", groups={"household"}),
     * @Assert\GreaterThanOrEqual(value=0, groups={"household"}),
     */
    public $m60;

    /**
     * @var string[] countrySpecific::id => countrySpecificAnswer::answer
     * @Assert\Count(max="0", groups={"member"})
     */
    public $countrySpecifics = [];

    /** @var string */
    private $countryIso3;

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
                    $this->$property = trim($value);
                } else {
                    $this->$property = $value;
                }
            }
        }

        $countrySpecifics = $entityManager->getRepository(CountrySpecific::class)->findBy(['countryIso3' => $countryIso3], ['id'=>'asc']);
        foreach ($countrySpecifics as $countrySpecific) {
            if (isset($content[$countrySpecific->getFieldString()])) {
                $this->countrySpecifics[$countrySpecific->getId()] = $content[$countrySpecific->getFieldString()];
            }
        }
    }

    /**
     * @Assert\IsTrue(message="Camp must have defined both Tent number and Camp name", payload={"propertyPath"="campName"})
     */
    public function isCampValid(): bool
    {
        return ($this->tentNumber && $this->campName) xor !($this->tentNumber || $this->campName);
    }

    /**
     * @Assert\IsTrue(message="Address must have defined street, number and postcode", payload={"propertyPath"="addressStreet"})
     */
    public function isAddressValid(): bool
    {
        return ($this->addressNumber && $this->addressPostcode && $this->addressStreet) xor !($this->addressNumber || $this->addressPostcode || $this->addressStreet);
    }

    /**
     * @Assert\IsTrue(message="Camp or address must be fully defined", payload={"propertyPath"="addressStreet"})
     */
    public function isAddressExists(): bool
    {
        return $this->isAddressValid() || $this->isCampValid();
    }

    /**
     * @Assert\IsTrue(message="There is no Adm1 like this", payload={"propertyPath"="adm1"})
     */
    public function isValidAdm1(): bool
    {
        if (!$this->adm1) {
            return false;
        }
        $location = $this->entityManager->getRepository(Location::class)->getByNames(
            $this->countryIso3,
            $this->adm1,
            null,
            null,
            null
        );
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
        $location = $this->entityManager->getRepository(Location::class)->getByNames(
            $this->countryIso3,
            $this->adm1,
            $this->adm2,
            null,
            null
        );
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
        $location = $this->entityManager->getRepository(Location::class)->getByNames(
            $this->countryIso3,
            $this->adm1,
            $this->adm2,
            $this->adm3,
            null
        );
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
        $location = $this->entityManager->getRepository(Location::class)->getByNames(
            $this->countryIso3,
            $this->adm1,
            $this->adm2,
            $this->adm3,
            $this->adm4
        );
        return null !== $location;
    }

    public function buildBeneficiaryInputType(): BeneficiaryInputType
    {
        $beneficiary = new BeneficiaryInputType();
        $beneficiary->setDateOfBirth(ImportDateConverter::toIso(ImportDateConverter::toDatetime($this->dateOfBirth)));
        $beneficiary->setLocalFamilyName($this->localFamilyName);
        $beneficiary->setLocalGivenName($this->localGivenName);
        $beneficiary->setLocalParentsName($this->localParentsName);
        $beneficiary->setEnFamilyName($this->englishFamilyName);
        $beneficiary->setEnGivenName($this->englishGivenName);
        $beneficiary->setEnParentsName($this->englishParentsName);
        $beneficiary->setGender($this->gender);
        $beneficiary->setResidencyStatus($this->residencyStatus);
        $beneficiary->setIsHead($this->head);

        if (!is_null($this->idType)) { //TODO check, that id card is filled completely
            $nationalId = new NationalIdCardInputType();
            $nationalId->setType($this->idType);
            $nationalId->setNumber((string) $this->idNumber);
            $beneficiary->addNationalIdCard($nationalId);
        }

        if (!is_null($this->numberPhone1)) { //TODO check, that phone is filled completely in import
            $phone1 = new PhoneInputType();
            $phone1->setNumber((string) $this->numberPhone1);
            $phone1->setType($this->typePhone1);
            $phone1->setPrefix((string) $this->prefixPhone1);
            $phone1->setProxy($this->proxyPhone1);
            $beneficiary->addPhone($phone1);
        }

        if (!is_null($this->numberPhone2)) { //TODO check, that phone is filled completely in import
            $phone2 = new PhoneInputType();
            $phone2->setNumber((string) $this->numberPhone2);
            $phone2->setType($this->typePhone2);
            $phone2->setPrefix((string) $this->prefixPhone2);
            $phone2->setProxy($this->proxyPhone2);
            $beneficiary->addPhone($phone2);
        }

        return $beneficiary;
    }
}
