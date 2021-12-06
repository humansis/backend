<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Integrity;

use BeneficiaryBundle\Utils\HouseholdExportCSVService;
use CommonBundle\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\Integrity\CellParameters;
use NewApiBundle\Validator\Constraints\ImportDate;
use Symfony\Component\Validator\Constraints as Assert;

class HouseholdMember
{
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
     * @ImportDate(),
     * @Assert\NotBlank(),
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
     * @Assert\Choice(choices=BeneficiaryBundle\Entity\Household::SHELTER_STATUSES)
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
                $this->$property = $content[$header][CellParameters::VALUE];
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
