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
class Beneficiary extends AbstractBeneficiary implements ExportableInterface
{
    /**
     * @ORM\OneToOne(targetEntity="Entity\Person", cascade={"persist", "remove"})
     */
    private $person;

    /**
     * @var bool
     *
     * @ORM\Column(name="status", type="boolean")
     * @Assert\NotBlank(message="The status is required.")
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="residency_status", type="string", length=20)
     * @Assert\Regex("/^(refugee|IDP|resident|returnee)$/i")
     */
    private $residencyStatus;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="updated_on", type="datetime", nullable=true)
     */
    private $updatedOn;

    /**
     * @var Household
     *
     * @ORM\ManyToOne(targetEntity="Entity\Household", inversedBy="beneficiaries")
     */
    private $household;

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
    private $smartcards;

    /**
     * @var ImportBeneficiary[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\ImportBeneficiary", mappedBy="beneficiary", cascade={"persist", "remove"})
     */
    private $importBeneficiaries;

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

    /**
     * @return Person
     */
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
     * @param VulnerabilityCriterion $vulnerabilityCriterion
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
     * @param VulnerabilityCriterion $vulnerabilityCriterion
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

    /**
     * @return string
     */
    public function getResidencyStatus(): string
    {
        return $this->residencyStatus;
    }

    /**
     * @param string $residencyStatus
     *
     * @return Beneficiary
     */
    public function setResidencyStatus(string $residencyStatus): self
    {
        $this->residencyStatus = $residencyStatus;

        return $this;
    }

    /**
     * Returns an array representation of this class in order to prepare the export
     *
     * @return array
     * @throws Exception
     */
    public function getMappedValueForExport(): array
    {
        // Recover the phones of the beneficiary
        $phoneTypes = ["", ""];
        $phonePrefix = ["", ""];
        $phoneValues = ["", ""];
        $phoneProxies = ["", ""];

        $index = 0;
        foreach ($this->getPerson()->getPhones()->getValues() as $value) {
            $phoneTypes[$index] = $value->getType();
            $phonePrefix[$index] = $value->getPrefix();
            $phoneValues[$index] = $value->getNumber();
            $phoneProxies[$index] = $value->getProxy();
            $index++;
        }

        // Recover the  criterions from Vulnerability criteria object
        $valuesCriteria = [];
        foreach ($this->getVulnerabilityCriteria()->getValues() as $value) {
            $valuesCriteria[] = $value->getFieldString();
        }
        $valuesCriteria = join(',', $valuesCriteria);

        $primaryDocument = $this->getPerson()->getPrimaryNationalId();
        $secondaryDocument = $this->getPerson()->getSecondaryNationalId();
        $tertiaryDocument = $this->getPerson()->getTertiaryNationalId();

        //Recover country specifics for the household
        $valueCountrySpecific = [];
        foreach ($this->getHousehold()->getCountrySpecificAnswers()->getValues() as $value) {
            $valueCountrySpecific[$value->getCountrySpecific()->getFieldString()] = $value->getAnswer();
        }

        if ($this->getPerson()->getGender() == PersonGender::FEMALE) {
            $valueGender = "Female";
        } else {
            $valueGender = "Male";
        }

        $householdLocations = $this->getHousehold()->getHouseholdLocations();

        $currentHouseholdLocation = null;

        /** @var HouseholdLocation $householdLocation */
        foreach ($householdLocations as $householdLocation) {
            if ($householdLocation->getLocationGroup() === HouseholdLocation::LOCATION_GROUP_CURRENT) {
                $currentHouseholdLocation = $householdLocation;
            }
        }

        $location = $currentHouseholdLocation->getLocation();

        $adm1 = $location->getAdm1Name();
        $adm2 = $location->getAdm2Name();
        $adm3 = $location->getAdm3Name();
        $adm4 = $location->getAdm4Name();

        $householdFields = $this->getCommonHouseholdExportFields();

        if ($this->status === true) {
            $finalArray = array_merge(
                ["household ID" => $this->getHousehold()->getId()],
                $householdFields,
                [
                    "adm1" => $adm1,
                    "adm2" => $adm2,
                    "adm3" => $adm3,
                    "adm4" => $adm4,
                ]
            );
        } else {
            $finalArray = [
                "household ID" => "",
                "addressStreet" => "",
                "addressNumber" => "",
                "addressPostcode" => "",
                "camp" => "",
                "tent number" => "",
                "livelihood" => "",
                "incomeLevel" => "",
                "foodConsumptionScore" => "",
                "copingStrategiesIndex" => "",
                "notes" => "",
                "latitude" => "",
                "longitude" => "",
                "adm1" => "",
                "adm2" => "",
                "adm3" => "",
                "adm4" => "",
            ];
        }

        $shelterStatus = '';
        if ($this->getHousehold()->getShelterStatus()) {
            $shelterStatus = $this->getHousehold()->getShelterStatus() ? $this->getHousehold()->getShelterStatus() : '';
        }

        $tempBenef = [
            "beneficiary ID" => $this->getId(),
            "localGivenName" => $this->getPerson()->getLocalGivenName(),
            "localFamilyName" => $this->getPerson()->getLocalFamilyName(),
            "enGivenName" => $this->getPerson()->getEnGivenName(),
            "enFamilyName" => $this->getPerson()->getEnFamilyName(),
            "gender" => $valueGender,
            "head" => $this->isHead() ? "true" : "false",
            "residencyStatus" => $this->getResidencyStatus(),
            "dateOfBirth" => $this->getPerson()->getDateOfBirth(),
            "vulnerabilityCriteria" => $valuesCriteria,
            "type phone 1" => $phoneTypes[0],
            "prefix phone 1" => $phonePrefix[0],
            "phone 1" => $phoneValues[0],
            "proxy phone 1" => $phoneProxies[0],
            "type phone 2" => $phoneTypes[1],
            "prefix phone 2" => $phonePrefix[1],
            "phone 2" => $phoneValues[1],
            "proxy phone 2" => $phoneProxies[1],
            "primary ID type" => $primaryDocument ? $primaryDocument->getIdType() : '',
            "primary ID number" => $primaryDocument ? $primaryDocument->getIdNumber() : '',
            "secondary ID type" => $secondaryDocument ? $secondaryDocument->getIdType() : '',
            "secondary ID number" => $secondaryDocument ? $secondaryDocument->getIdNumber() : '',
            "tertiary ID type" => $tertiaryDocument ? $tertiaryDocument->getIdType() : '',
            "tertiary ID number" => $tertiaryDocument ? $tertiaryDocument->getIdNumber() : '',
            "Assets" => implode(', ', $this->getHousehold()->getAssets()),
            "Shelter Status" => $shelterStatus,
            "Debt Level" => $this->getHousehold()->getDebtLevel(),
            "Support Received Types" => implode(', ', $this->getHousehold()->getSupportReceivedTypes()),
            "Support Date Received" => $this->getHousehold()->getSupportDateReceived()
                ? $this->getHousehold()->getSupportDateReceived()->format('d-m-Y')
                : null,
        ];

        foreach ($valueCountrySpecific as $key => $value) {
            $finalArray[$key] = $value;
        }

        foreach ($tempBenef as $key => $value) {
            $finalArray[$key] = $value;
        }

        return $finalArray;
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
            array_map(function ($value) {
                return HouseholdSupportReceivedType::valueFromAPI($value);
            }, (array) $this->getHousehold()->getSupportReceivedTypes())
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
            $referral_type = $this->$this->getPerson()->getReferral()->getType();
            $referral_comment = $this->$this->getPerson()->getReferral()->getComment();
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
     *
     * @return int|null
     */
    public function getAge(): ?int
    {
        if ($this->person->getDateOfBirth()) {
            try {
                return $this->person->getDateOfBirth()->diff(new DateTime('now'))->y;
            } catch (Exception $ex) {
                return null;
            }
        }

        return null;
    }

    /**
     * @return string|null
     */
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
    public function getImportBeneficiaries()
    {
        return $this->importBeneficiaries;
    }
}
