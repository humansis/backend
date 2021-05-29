<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Integrity;

use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Utils\HouseholdExportCSVService;
use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Validator\Constraints\ImportDate;
use Symfony\Component\Validator\Constraints as Assert;

class HouseholdHead
{
    use HouseholdInputBuilderTrait;

    /**
     * @Assert\Type("string")
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
     * @Assert\Choice(choices=ProjectBundle\Enum\Livelihood::TRANSLATIONS)
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
     * @Assert\NotBlank
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
     * @Assert\Choice({"Male", "Female"}),
     * @Assert\NotBlank(),
     */
    protected $gender;

    /**
     * @Assert\Choice({"true", "false"}),
     * @Assert\NotBlank(),
     */
    protected $head;

    /**
     * @Assert\Choice({"Refugee", "IDP", "Resident", "Returnee"}),
     * @Assert\NotBlank(),
     */
    protected $residencyStatus;

    /**
     * @ ImportDate()
     * @Assert\Type("string")
     * @Assert\NotBlank()
     */
    protected $dateOfBirth;

    /**
     * @Assert\Type("string")
     */
    protected $vulnerabilityCriteria;

    /**
     * @Assert\Choice({"Mobile", "Landline"}),
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
     * @Assert\Choice({"Y", "N"}),
     */
    protected $proxyPhone1;

    /**
     * @Assert\Choice({"Mobile", "Landline"}),
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
     * @Assert\Choice({"Y", "N"}),
     */
    protected $proxyPhone2;

    /**
     * @Assert\Type("scalar")
     */
    protected $idType;

    /**
     * @Assert\Type("scalar")
     */
    protected $idNumber;

    /**
     * @Assert\Choice(choices=\BeneficiaryBundle\Entity\Household::SHELTER_STATUSES)
     */
    protected $shelterStatus;

    /**
     * @Assert\Type("string")
     */
    protected $assets;

    /**
     * @Assert\Type("numeric")
     */
    protected $debtLevel;

    /**
     * @Assert\Type("string")
     */
    protected $supportReceivedTypes;

    /**
     * @ImportDate(),
     */
    protected $supportDateReceived;

    /**
     * @Assert\Type("integer"),
     * @Assert\GreaterThanOrEqual(0),
     */
    protected $f0;

    /**
     * @Assert\Type("integer"),
     * @Assert\GreaterThanOrEqual(0),
     */
    protected $f2;

    /**
     * @Assert\Type("integer"),
     * @Assert\GreaterThanOrEqual(0),
     */
    protected $f6;

    /**
     * @Assert\Type("integer"),
     * @Assert\GreaterThanOrEqual(0),
     */
    protected $f18;

    /**
     * @Assert\Type("integer"),
     * @Assert\GreaterThanOrEqual(0),
     */
    protected $f60;

    /**
     * @Assert\Type("integer"),
     * @Assert\GreaterThanOrEqual(0),
     */
    protected $m0;

    /**
     * @Assert\Type("integer"),
     * @Assert\GreaterThanOrEqual(0),
     */
    protected $m2;

    /**
     * @Assert\Type("integer"),
     * @Assert\GreaterThanOrEqual(0),
     */
    protected $m6;

    /**
     * @Assert\Type("integer"),
     * @Assert\GreaterThanOrEqual(0),
     */
    protected $m18;

    /**
     * @Assert\Type("integer"),
     * @Assert\GreaterThanOrEqual(0),
     */
    protected $m60;

    protected $countrySpecifics = [];

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
                $this->$property = $content[$header];
            }
        }

        $countrySpecifics = $entityManager->getRepository(CountrySpecific::class)->findByCountryIso3($countryIso3);
        foreach ($countrySpecifics as $countrySpecific) {
            if (isset($content[$countrySpecific->getFieldString()])) {
                $this->countrySpecifics[$countrySpecific->getFieldString()] = $content[$countrySpecific->getFieldString()];
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
        $adm = $this->entityManager->getRepository(Adm1::class)->findOneBy(['countryISO3' => $this->countryIso3, 'name' => $this->adm1]);

        return null !== $adm;
    }

    /**
     * @Assert\IsTrue(message="There is no Adm2 in this location", payload={"propertyPath"="adm2"})
     */
    public function isValidAdm2(): bool
    {
        if (!$this->adm2) {
            return true;
        }

        $adm1 = $this->entityManager->getRepository(Adm1::class)->findOneBy(['name' => $this->adm1, 'countryISO3' => $this->countryIso3]);
        $adm2 = $this->entityManager->getRepository(Adm2::class)->findOneBy(['name' => $this->adm2, 'adm1' => $adm1]);

        return null !== $adm2;
    }

    /**
     * @Assert\IsTrue(message="There is no Adm3 in this location", payload={"propertyPath"="adm3"})
     */
    public function isValidAdm3(): bool
    {
        if (!$this->adm3) {
            return true;
        }

        $adm1 = $this->entityManager->getRepository(Adm1::class)->findOneBy(['name' => $this->adm1, 'countryISO3' => $this->countryIso3]);
        $adm2 = $this->entityManager->getRepository(Adm2::class)->findOneBy(['name' => $this->adm2, 'adm1' => $adm1]);
        $adm3 = $this->entityManager->getRepository(Adm3::class)->findOneBy(['name' => $this->adm3, 'adm2' => $adm2]);

        return null !== $adm3;
    }

    /**
     * @Assert\IsTrue(message="There is no Adm4 in this location", payload={"propertyPath"="adm4"})
     */
    public function isValidAdm4(): bool
    {
        if (!$this->adm4) {
            return true;
        }

        $adm1 = $this->entityManager->getRepository(Adm1::class)->findOneBy(['name' => $this->adm1, 'countryISO3' => $this->countryIso3]);
        $adm2 = $this->entityManager->getRepository(Adm2::class)->findOneBy(['name' => $this->adm2, 'adm1' => $adm1]);
        $adm3 = $this->entityManager->getRepository(Adm3::class)->findOneBy(['name' => $this->adm3, 'adm2' => $adm2]);
        $adm4 = $this->entityManager->getRepository(Adm4::class)->findOneBy(['name' => $this->adm4, 'adm3' => $adm3]);

        return null !== $adm4;
    }

    /**
     * @Assert\Choice(choices=\BeneficiaryBundle\Entity\Household::ASSETS, multiple=true)
     * @return array
     */
    public function getAssets(): array
    {
        if (empty($this->assets)) {
            return [];
        }

        return explode(',', $this->assets);
    }

    /**
     * @Assert\Choice(choices=\BeneficiaryBundle\Entity\Household::SUPPORT_RECIEVED_TYPES, multiple=true)
     * @return array
     */
    public function getSupportReceivedTypes(): array
    {
        if (empty($this->supportReceivedTypes)) {
            return [];
        }
        return explode(',', $this->supportReceivedTypes);
    }
}
