<?php

namespace Entity;

use DateTimeInterface;
use Utils\ExportableInterface;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Enum\HouseholdAssets;
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
     * Set enGivenName.
     *
     * @param string|null $enGivenName
     *
     * @return Beneficiary
     * @deprecated
     */
    public function setEnGivenName($enGivenName): self
    {
        $this->person->setEnGivenName($enGivenName);

        return $this;
    }

    /**
     * Get enGivenName.
     *
     * @return string|null
     * @deprecated
     */
    public function getEnGivenName(): ?string
    {
        return $this->person->getEnGivenName();
    }

    /**
     * Set enFamilyName.
     *
     * @param string|null $enFamilyName
     *
     * @return Beneficiary
     * @deprecated
     */
    public function setEnFamilyName($enFamilyName): self
    {
        $this->person->setEnFamilyName($enFamilyName);

        return $this;
    }

    /**
     * Get enFamilyName.
     *
     * @return string|null
     * @deprecated
     */
    public function getEnFamilyName(): ?string
    {
        return $this->person->getEnFamilyName();
    }

    /**
     * Set localGivenName.
     *
     * @param string|null $localGivenName
     *
     * @return Beneficiary
     * @deprecated
     */
    public function setLocalGivenName($localGivenName): self
    {
        $this->person->setLocalGivenName($localGivenName);

        return $this;
    }

    /**
     * Get localGivenName.
     *
     * @return string|null
     * @deprecated
     */
    public function getLocalGivenName(): ?string
    {
        return $this->person->getLocalGivenName();
    }

    /**
     * Set localFamilyName.
     *
     * @param string|null $localFamilyName
     *
     * @return Beneficiary
     * @deprecated
     */
    public function setLocalFamilyName($localFamilyName): self
    {
        $this->person->setLocalFamilyName($localFamilyName);

        return $this;
    }

    /**
     * Get localFamilyName.
     *
     * @return string|null
     * @deprecated
     */
    public function getLocalFamilyName(): ?string
    {
        return $this->person->getLocalFamilyName();
    }

    /**
     * Set gender.
     *
     * @param int|null $gender one of Person::GENDER_*
     *
     * @return Beneficiary
     * @deprecated
     */
    public function setGender($gender): self
    {
        $this->person->setGender($gender);

        return $this;
    }

    /**
     * Get gender.
     *
     * @return string|null one of Person::GENDER_*
     * @deprecated
     *
     */
    public function getGender(): ?string
    {
        return $this->person->getGender();
    }

    /**
     * Set dateOfBirth.
     *
     * @param DateTime|null $dateOfBirth
     *
     * @return Beneficiary
     * @deprecated
     */
    public function setDateOfBirth($dateOfBirth): self
    {
        $this->person->setDateOfBirth($dateOfBirth);

        return $this;
    }

    /**
     * Get dateOfBirth.
     *
     * @return DateTime|null
     * @deprecated
     */
    public function getDateOfBirthObject(): ?DateTimeInterface
    {
        return $this->person->getDateOfBirth();
    }

    /**
     * @return string|null
     * @deprecated
     */
    public function getDateOfBirth(): ?string
    {
        return $this->person->getDateOfBirth() ? $this->person->getDateOfBirth()->format('d-m-Y') : null;
    }

    /**
     * Set updatedOn.
     *
     * @param DateTime|null $updatedOn
     *
     * @return Beneficiary
     */
    public function setUpdatedOn($updatedOn = null)
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }

    /**
     * Get updatedOn.
     *
     * @return DateTime|null
     */
    public function getUpdatedOn()
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
    public function setHousehold(Household $household = null)
    {
        $this->household = $household;

        return $this;
    }

    /**
     * Get household.
     *
     * @return Household|null
     */
    public function getHousehold()
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
    public function addVulnerabilityCriterion(VulnerabilityCriterion $vulnerabilityCriterion)
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
    public function removeVulnerabilityCriterion(VulnerabilityCriterion $vulnerabilityCriterion)
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
    public function setVulnerabilityCriteria(Collection $collection = null)
    {
        $this->vulnerabilityCriteria = $collection;

        return $this;
    }

    /**
     * Add phone.
     *
     * @param Phone $phone
     *
     * @return Beneficiary
     * @deprecated
     */
    public function addPhone(Phone $phone): self
    {
        $this->person->addPhone($phone);

        return $this;
    }

    /**
     * Remove phone.
     *
     * @param Phone $phone
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     * @deprecated
     */
    public function removePhone(Phone $phone): bool
    {
        return $this->person->removePhone($phone);
    }

    /**
     * Get phones.
     *
     * @return Collection
     * @deprecated
     */
    public function getPhones(): Collection
    {
        return $this->person->getPhones();
    }

    /**
     * Set phones.
     *
     * @param $collection
     *
     * @return Beneficiary
     * @deprecated
     */
    public function setPhones(Collection $collection = null): self
    {
        $this->person->setPhones($collection);

        return $this;
    }

    /**
     * Set nationalId.
     *
     * @param  $collection
     *
     * @return Beneficiary
     * @deprecated
     */
    public function setNationalIds(Collection $collection = null): self
    {
        $this->person->setNationalIds($collection);

        return $this;
    }

    /**
     * Add nationalId.
     *
     * @param NationalId $nationalId
     *
     * @return Beneficiary
     * @deprecated
     */
    public function addNationalId(NationalId $nationalId): self
    {
        $this->person->addNationalId($nationalId);

        return $this;
    }

    /**
     * Remove nationalId.
     *
     * @param NationalId $nationalId
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     * @deprecated
     */
    public function removeNationalId(NationalId $nationalId): bool
    {
        return $this->person->removeNationalId($nationalId);
    }

    /**
     * Get nationalIds.
     *
     * @return NationalId[]|Collection
     * @deprecated
     */
    public function getNationalIds(): Collection
    {
        return $this->person->getNationalIds();
    }

    /**
     * Set status.
     *
     * @param bool $status
     *
     * @return Beneficiary
     * @deprecated use setHead() instead
     */
    public function setStatus($status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return bool
     * @deprecated use isHead() instead
     */
    public function getStatus(): bool
    {
        return $this->status;
    }

    public function setHead(bool $isHead = true): self
    {
        return $this->setStatus($isHead);
    }

    public function isHead(): bool
    {
        return $this->getStatus();
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
    public function setResidencyStatus($residencyStatus): self
    {
        $this->residencyStatus = $residencyStatus;

        return $this;
    }

    /**
     * Set profile.
     *
     * @param Profile|null $profile
     *
     * @return Beneficiary
     * @deprecated
     */
    public function setProfile(Profile $profile = null): self
    {
        $this->person->setProfile($profile);

        return $this;
    }

    /**
     * Get profile.
     *
     * @return Profile|null
     * @deprecated
     */
    public function getProfile(): ?Profile
    {
        return $this->person->getProfile();
    }

    /**
     * Set referral.
     *
     * @param Referral|null $referral
     *
     * @return Beneficiary
     * @deprecated
     */
    public function setReferral(Referral $referral = null)
    {
        $this->person->setReferral($referral);

        return $this;
    }

    /**
     * Get referral.
     *
     * @return Referral|null
     * @deprecated
     */
    public function getReferral(): ?Referral
    {
        return $this->person->getReferral();
    }

    /**
     * Returns an array representation of this class in order to prepare the export
     *
     * @return array
     */
    public function getMappedValueForExport(): array
    {
        // Recover the phones of the beneficiary
        $typephones = ["", ""];
        $prefixphones = ["", ""];
        $valuesphones = ["", ""];
        $proxyphones = ["", ""];

        $index = 0;
        foreach ($this->getPhones()->getValues() as $value) {
            $typephones[$index] = $value->getType();
            $prefixphones[$index] = $value->getPrefix();
            $valuesphones[$index] = $value->getNumber();
            $proxyphones[$index] = $value->getProxy();
            $index++;
        }

        // Recover the  criterions from Vulnerability criteria object
        $valuescriteria = [];
        foreach ($this->getVulnerabilityCriteria()->getValues() as $value) {
            array_push($valuescriteria, $value->getFieldString());
        }
        $valuescriteria = join(',', $valuescriteria);

        /** @var NationalId $primaryDocument */
        $primaryDocument = null;
        /** @var NationalId $secondaryDocument */
        $secondaryDocument = null;
        /** @var NationalId $ternaryDocument */
        $ternaryDocument = null;
        foreach ($this->getNationalIds()->getValues() as $value) {
            if ($value->getPriority() === 1) {
                $primaryDocument = $value;
            }
            if ($value->getPriority() === 2) {
                $secondaryDocument = $value;
            }
            if ($value->getPriority() === 3) {
                $ternaryDocument = $value;
            }
        }

        //Recover country specifics for the household
        $valueCountrySpecific = [];
        foreach ($this->getHousehold()->getCountrySpecificAnswers()->getValues() as $value) {
            $valueCountrySpecific[$value->getCountrySpecific()->getFieldString()] = $value->getAnswer();
        }

        if ($this->getGender() == PersonGender::FEMALE) {
            $valueGender = "Female";
        } else {
            $valueGender = "Male";
        }

        $householdLocations = $this->getHousehold()->getHouseholdLocations();
        $currentHouseholdLocation = null;
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

        $supportReceivedTypes = [];
        foreach ((array) $this->getHousehold()->getSupportReceivedTypes() as $type) {
            $supportReceivedTypes[] = HouseholdSupportReceivedType::valueToAPI($type);
        }

        $shelterStatus = '';
        if ($this->getHousehold()->getShelterStatus()) {
            $shelterStatus = $this->getHousehold()->getShelterStatus() ? $this->getHousehold()->getShelterStatus() : '';
        }

        $tempBenef = [
            "beneficiary ID" => $this->getId(),
            "localGivenName" => $this->getLocalGivenName(),
            "localFamilyName" => $this->getLocalFamilyName(),
            "enGivenName" => $this->getEnGivenName(),
            "enFamilyName" => $this->getEnFamilyName(),
            "gender" => $valueGender,
            "head" => $this->isHead() ? "true" : "false",
            "residencyStatus" => $this->getResidencyStatus(),
            "dateOfBirth" => $this->getDateOfBirth(),
            "vulnerabilityCriteria" => $valuescriteria,
            "type phone 1" => $typephones[0],
            "prefix phone 1" => $prefixphones[0],
            "phone 1" => $valuesphones[0],
            "proxy phone 1" => $proxyphones[0],
            "type phone 2" => $typephones[1],
            "prefix phone 2" => $prefixphones[1],
            "phone 2" => $valuesphones[1],
            "proxy phone 2" => $proxyphones[1],
            "primary ID type" => $primaryDocument ? $primaryDocument->getIdType() : '',
            "primary ID number" => $primaryDocument ? $primaryDocument->getIdNumber() : '',
            "secondary ID type" => $secondaryDocument ? $secondaryDocument->getIdType() : '',
            "secondary ID number" => $secondaryDocument ? $secondaryDocument->getIdNumber() : '',
            "ternary ID type" => $ternaryDocument ? $ternaryDocument->getIdType() : '',
            "ternary ID number" => $ternaryDocument ? $ternaryDocument->getIdNumber() : '',
            "Assets" => implode(', ', $this->getHousehold()->getAssets()),
            "Shelter Status" => $shelterStatus,
            "Debt Level" => $this->getHousehold()->getDebtLevel(),
            "Support Received Types" => implode(', ', $supportReceivedTypes),
            "Support Date Received" => $this->getHousehold()->getSupportDateReceived() ? $this->getHousehold(
            )->getSupportDateReceived()->format('d-m-Y') : null,
        ];

        foreach ($valueCountrySpecific as $key => $value) {
            $finalArray[$key] = $value;
        }

        foreach ($tempBenef as $key => $value) {
            $finalArray[$key] = $value;
        }

        return $finalArray;
    }

    public function getCommonBeneficiaryExportFields()
    {
        $gender = '';
        if ($this->getGender() == PersonGender::FEMALE) {
            $gender = 'Female';
        } else {
            $gender = 'Male';
        }

        return [
            "Local Given Name" => $this->getLocalGivenName(),
            "Local Family Name" => $this->getLocalFamilyName(),
            "English Given Name" => $this->getEnGivenName(),
            "English Family Name" => $this->getEnFamilyName(),
            "Gender" => $gender,
            "Date Of Birth" => $this->getDateOfBirth(),
        ];
    }

    public function getCommonHouseholdExportFields()
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

    public function getCommonExportFields()
    {
        $referral_type = null;
        $referral_comment = null;
        if ($this->getReferral()) {
            $referral_type = $this->getReferral()->getType();
            $referral_comment = $this->getReferral()->getComment();
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
        if ($this->getDateOfBirthObject()) {
            try {
                return $this->getDateOfBirthObject()->diff(new DateTime('now'))->y;
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

    /**
     * Get localParentsName.
     *
     * @return string|null
     * @deprecated
     */
    public function getLocalParentsName(): ?string
    {
        return $this->person->getLocalParentsName();
    }

    /**
     * Set localParentsName.
     *
     * @param string|null $localParentsName
     * @return Beneficiary
     * @deprecated
     */
    public function setLocalParentsName(?string $localParentsName): self
    {
        $this->person->setLocalParentsName($localParentsName);

        return $this;
    }

    /**
     * Get enParentsName.
     *
     * @return string|null
     * @deprecated
     */
    public function getEnParentsName(): ?string
    {
        return $this->person->getEnParentsName();
    }

    /**
     * Set enParentsName.
     *
     * @param string|null $enParentsName
     * @return Beneficiary
     * @deprecated
     */
    public function setEnParentsName(?string $enParentsName): self
    {
        $this->person->setEnParentsName($enParentsName);

        return $this;
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
