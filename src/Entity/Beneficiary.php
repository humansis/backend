<?php

namespace Entity;

use Utils\ExportableInterface;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Enum\HouseholdShelterStatus;
use Enum\HouseholdSupportReceivedType;
use Enum\PersonGender;
use Enum\Livelihood;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Beneficiary
 *
 * @ORM\Table(name="beneficiary")
 * @ORM\Entity(repositoryClass="Repository\BeneficiaryRepository")
 */
class Beneficiary extends AbstractBeneficiary
{
    /**
     * @ORM\OneToOne(targetEntity="Entity\Person", cascade={"persist", "remove"})
     */
    private $person;

    /**
     * @ORM\Column(name="status", type="boolean")
     */
    #[Assert\NotBlank(message: 'The status is required.')]
    private bool $status;

    /**
     * @ORM\Column(name="residency_status", type="string", length=20)
     */
    #[Assert\Regex('/^(refugee|IDP|resident|returnee)$/i')]
    private string $residencyStatus;

    /**
     * @ORM\Column(name="updated_on", type="datetime", nullable=true)
     */
    private ?DateTime $updatedOn;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Household", inversedBy="beneficiaries")
     */
    private ?Household $household = null;

    /**
     * @var VulnerabilityCriterion
     *
     * @ORM\ManyToMany(targetEntity="Entity\VulnerabilityCriterion", cascade={"persist"})
     */
    private $vulnerabilityCriteria;

    /**
     * @ORM\OneToMany(targetEntity="Entity\Smartcard", mappedBy="beneficiary")
     *
     * @var Collection|Smartcard[]
     */
    private Collection |array $smartcards;

    /**
     * @var ImportBeneficiary[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\ImportBeneficiary", mappedBy="beneficiary", cascade={"persist", "remove"})
     */
    private array| Collection $importBeneficiaries;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->vulnerabilityCriteria = new ArrayCollection();
        $this->person = new Person();
        $this->smartcards = new ArrayCollection();
        $this->setUpdatedOn(new DateTime());
        $this->importBeneficiaries = new ArrayCollection();
        //TODO check if updatedOn everytime
    }

    /**
     * Get HHId.
     *
     * @return int
     */
    public function getHouseholdId(): ?int
    {
        return $this->getHousehold() ? $this->getHousehold()->getId() : null;
    }

    public function getPerson(): Person
    {
        return $this->person;
    }

    /**
     * Set updatedOn.
     *
     * @param DateTime|null $updatedOn
     *
     * @return Beneficiary
     */
    public function setUpdatedOn(?DateTime $updatedOn = null): Beneficiary
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }

    /**
     * Get updatedOn.
     *
     * @return DateTime|null
     */
    public function getUpdatedOn(): ?DateTime
    {
        return $this->updatedOn;
    }

    /**
     * Set household.
     *
     * @param Household|null $household
     *
     * @return Beneficiary
     */
    public function setHousehold(?Household $household = null): Beneficiary
    {
        $this->household = $household;

        return $this;
    }

    /**
     * Get household.
     *
     * @return Household|null
     */
    public function getHousehold(): ?Household
    {
        return $this->household;
    }

    /**
     * Add vulnerabilityCriterion.
     *
     *
     * @return Beneficiary
     */
    public function addVulnerabilityCriterion(VulnerabilityCriterion $vulnerabilityCriterion): Beneficiary
    {
        if (!$this->vulnerabilityCriteria->contains($vulnerabilityCriterion)) {
            $this->vulnerabilityCriteria[] = $vulnerabilityCriterion;
        }

        return $this;
    }

    /**
     * Remove vulnerabilityCriterion.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeVulnerabilityCriterion(VulnerabilityCriterion $vulnerabilityCriterion): bool
    {
        return $this->vulnerabilityCriteria->removeElement($vulnerabilityCriterion);
    }

    /**
     * Get vulnerabilityCriterion.
     *
     * @return Collection
     */
    public function getVulnerabilityCriteria()
    {
        return $this->vulnerabilityCriteria;
    }

    /**
     * Set VulnerabilityCriterions.
     *
     * @param Collection|null $collection
     *
     * @return Beneficiary
     */
    public function setVulnerabilityCriteria(Collection $collection = null): Beneficiary
    {
        $this->vulnerabilityCriteria = $collection;

        return $this;
    }

    public function setHead(bool $isHead = true): self
    {
        $this->status = $isHead;

        return $this;
    }

    public function isHead(): bool
    {
        return $this->status;
    }

    public function getResidencyStatus(): string
    {
        return $this->residencyStatus;
    }

    /**
     * @param string $residencyStatus
     */
    public function setResidencyStatus(string $residencyStatus): self
    {
        $this->residencyStatus = $residencyStatus;

        return $this;
    }



    public function getCommonBeneficiaryExportFields(): array
    {
        if ($this->getPerson()->getGender() == PersonGender::FEMALE) {
            $gender = 'Female';
        } else {
            $gender = 'Male';
        }

        return [
            "Local Given Name" => $this->getPerson()->getLocalGivenName(),
            "Local Family Name" => $this->getPerson()->getLocalFamilyName(),
            "English Given Name" => $this->getPerson()->getEnGivenName(),
            "English Family Name" => $this->getPerson()->getEnFamilyName(),
            "Gender" => $gender,
            "Date Of Birth" => $this->getPerson()->getDateOfBirth(),
        ];
    }

    public function getCommonHouseholdExportFields(): array
    {
        $householdLocations = $this->getHousehold()->getHouseholdLocations();
        $currentHouseholdLocation = null;
        foreach ($householdLocations as $householdLocation) {
            if ($householdLocation->getLocationGroup() === HouseholdLocation::LOCATION_GROUP_CURRENT) {
                $currentHouseholdLocation = $householdLocation;
            }
        }

        $camp = null;
        $tentNumber = null;
        $addressNumber = null;
        $addressStreet = null;
        $addressPostcode = null;

        if ($currentHouseholdLocation->getType() === HouseholdLocation::LOCATION_TYPE_CAMP) {
            $camp = $currentHouseholdLocation->getCampAddress()->getCamp()->getName();
            $tentNumber = $currentHouseholdLocation->getCampAddress()->getTentNumber();
        } else {
            $addressNumber = $currentHouseholdLocation->getAddress()->getNumber();
            $addressStreet = $currentHouseholdLocation->getAddress()->getStreet();
            $addressPostcode = $currentHouseholdLocation->getAddress()->getPostcode();
        }

        $livelihood = null;
        if (null !== $this->getHousehold()->getLivelihood()) {
            $livelihood = Livelihood::translate($this->getHousehold()->getLivelihood());
        }

        $shelterStatus = null;
        if (null !== $this->getHousehold()->getShelterStatus()) {
            $shelterStatus = HouseholdShelterStatus::valueToAPI($this->getHousehold()->getShelterStatus());
        }

        $supportReceivedTypes = array_values(
            array_map(fn($value) => HouseholdSupportReceivedType::valueFromAPI($value), (array) $this->getHousehold()->getSupportReceivedTypes())
        );

        $supportDateReceived = null;
        if (null !== $this->getHousehold()->getSupportDateReceived()) {
            $supportDateReceived = $this->getHousehold()->getSupportDateReceived()->format("m/d/Y");
        }

        return [
            "addressStreet" => $addressStreet,
            "addressNumber" => $addressNumber,
            "addressPostcode" => $addressPostcode,
            "camp" => $camp,
            "tent number" => $tentNumber,
            "livelihood" => $livelihood,
            "incomeLevel" => $this->getHousehold()->getIncome(),
            "foodConsumptionScore" => $this->getHousehold()->getFoodConsumptionScore(),
            "copingStrategiesIndex" => $this->getHousehold()->getCopingStrategiesIndex(),
            "notes" => $this->getHousehold()->getNotes(),
            "Enumerator name" => $this->getHousehold()->getEnumeratorName(),
            "latitude" => $this->getHousehold()->getLatitude(),
            "longitude" => $this->getHousehold()->getLongitude(),
            "Assets" => implode(', ', $this->getHousehold()->getAssets()),
            "Shelter Status" => $shelterStatus,
            "Debt Level" => $this->getHousehold()->getDebtLevel(),
            "Support Received Types" => implode(', ', $supportReceivedTypes),
            "Support Date Received" => $supportDateReceived,
        ];
    }

    public function getCommonExportFields(): array
    {
        $referral_type = null;
        $referral_comment = null;
        if ($this->getPerson()->getReferral()) {
            $referral_type = $this->getPerson()->getReferral()->getType();
            $referral_comment = $this->getPerson()->getReferral()->getComment();
        }

        $referralInfo = [
            "Referral Type" => $referral_type ? Referral::REFERRALTYPES[$referral_type] : null,
            "Referral Comment" => $referral_comment,
        ];

        return array_merge(
            $this->getCommonHouseholdExportFields(),
            $this->getCommonBeneficiaryExportFields(),
            $referralInfo
        );
    }

    /**
     * Returns age of beneficiary in years
     */
    public function getAge(): ?int
    {
        if ($this->person->getDateOfBirth()) {
            try {
                return $this->person->getDateOfBirth()->diff(new DateTime('now'))->y;
            } catch (Exception) {
                return null;
            }
        }

        return null;
    }

    public function getSmartcardSerialNumber(): ?string
    {
        foreach ($this->smartcards as $smartcard) {
            if ($smartcard->isActive()) {
                return $smartcard->getSerialNumber();
            }
        }

        return null;
    }

    public function hasVulnerabilityCriteria(string $vulnerabilityCriteria): bool
    {
        foreach ($this->getVulnerabilityCriteria() as $criterion) {
            if ($criterion->getFieldString() === $vulnerabilityCriteria) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection|ImportBeneficiary[]
     */
    public function getImportBeneficiaries(): Collection |array
    {
        return $this->importBeneficiaries;
    }

    public function getActiveSmartcard(): null|Smartcard
    {
        foreach ($this->smartcards as $smartcard) {
            if ($smartcard->isActive()) {
                return $smartcard;
            }
        }

        return null;
    }
}
