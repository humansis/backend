<?php

namespace BeneficiaryBundle\Entity;

use CommonBundle\Utils\ExportableInterface;
use DateTime;
use DistributionBundle\Entity\DistributionBeneficiary;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use Symfony\Component\Validator\Constraints as Assert;
use VoucherBundle\Entity\Smartcard;

/**
 * Beneficiary
 *
 * @ORM\Table(name="beneficiary")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\BeneficiaryRepository")
 */
class Beneficiary extends AbstractBeneficiary implements ExportableInterface
{
    /**
     * @ORM\OneToOne(targetEntity="BeneficiaryBundle\Entity\Person", cascade={"persist", "remove"})
     */
    private $person;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean")
     * @SymfonyGroups({"FullHousehold", "FullReceivers", "ValidatedDistribution", "SmallHousehold"})
     * @Assert\NotBlank(message="The status is required.")
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="residency_status", type="string", length=20)
     * @SymfonyGroups({"FullHousehold", "FullReceivers", "ValidatedDistribution", "SmallHousehold", "FullBeneficiary"})
     * @Assert\Regex("/^(refugee|IDP|resident)$/i")
     */
    private $residencyStatus;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="updated_on", type="datetime", nullable=true)
     * DateTime<'d-m-Y H:m:i'>
     * @SymfonyGroups({"FullHousehold", "FullBeneficiary"})
     */
    private $updatedOn;

    /**
     * @var Household
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Household", inversedBy="beneficiaries")
     */
    private $household;

    /**
     * @var VulnerabilityCriterion
     *
     * @ORM\ManyToMany(targetEntity="BeneficiaryBundle\Entity\VulnerabilityCriterion", cascade={"persist"})
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedDistribution", "FullBeneficiary"})
     */
    private $vulnerabilityCriteria;

    /**
     * @ORM\OneToMany(targetEntity="VoucherBundle\Entity\Smartcard", mappedBy="beneficiary")
     *
     * @var Collection|Smartcard[]
     */
    private $smartcards;

    /**
     * @var string
     * @ SymfonyGroups({"FullHousehold", "SmallHousehold", "ValidatedDistribution", "FullBeneficiary"})
     */
    private $smartcard;

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

        //TODO check if updatedOn everytime
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
     * @deprecated
     * @param string $enGivenName
     *
     * @return Beneficiary
     */
    public function setEnGivenName($enGivenName): self
    {
        $this->person->setEnGivenName($enGivenName);

        return $this;
    }

    /**
     * Get enGivenName.
     * @deprecated
     * @SymfonyGroups({"FullHousehold", "SmallHousehold","FullBeneficiary"})
     * @return string
     */
    public function getEnGivenName(): string
    {
        return $this->person->getEnGivenName();
    }

    /**
     * Set enFamilyName.
     * @deprecated
     * @param string $enFamilyName
     *
     * @return Beneficiary
     */
    public function setEnFamilyName($enFamilyName): self
    {
        $this->person->setEnFamilyName($enFamilyName);

        return $this;
    }

    /**
     * Get enFamilyName.
     * @deprecated
     * @SymfonyGroups({"FullHousehold", "SmallHousehold","FullBeneficiary"})
     * @return string
     */
    public function getEnFamilyName(): ?string
    {
        return $this->person->getEnFamilyName();
    }

    /**
     * Set localGivenName.
     * @deprecated
     * @param string $localGivenName
     *
     * @return Beneficiary
     */
    public function setLocalGivenName($localGivenName): self
    {
        $this->person->setLocalGivenName($localGivenName);
        return $this;
    }

    /**
     * Get localGivenName.
     * @deprecated
     * @SymfonyGroups({"FullHousehold", "SmallHousehold","FullBeneficiary"})
     * @return string
     */
    public function getLocalGivenName(): ?string
    {
        return $this->person->getLocalGivenName();
    }

    /**
     * Set localFamilyName.
     * @deprecated
     * @param string $localFamilyName
     *
     * @return Beneficiary
     */
    public function setLocalFamilyName($localFamilyName): self
    {
        $this->person->setLocalFamilyName($localFamilyName);

        return $this;
    }

    /**
     * Get localFamilyName.
     * @deprecated
     * @SymfonyGroups({"FullHousehold", "SmallHousehold","FullBeneficiary"})
     * @return string
     */
    public function getLocalFamilyName(): ?string
    {
        return $this->person->getLocalFamilyName();
    }

    /**
     * Set gender.
     * @deprecated
     * @param int $gender
     *
     * @return Beneficiary
     */
    public function setGender($gender): self
    {
        $this->person->setGender($gender);
        return $this;
    }

    /**
     * Get gender.
     * @deprecated
     * @SymfonyGroups({"FullHousehold", "SmallHousehold","FullBeneficiary"})
     * @return int
     */
    public function getGender(): int
    {
        return $this->person->getGender();
    }

    /**
     * Set dateOfBirth.
     * @deprecated
     * @param DateTime $dateOfBirth
     *
     * @return Beneficiary
     */
    public function setDateOfBirth($dateOfBirth): self
    {
        $this->person->setDateOfBirth($dateOfBirth);

        return $this;
    }

    /**
     * Get dateOfBirth.
     * @deprecated
     * @SymfonyGroups({"FullHousehold", "SmallHousehold","FullBeneficiary"})
     * @return DateTime
     */
    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->person->getDateOfBirth();
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
        $this->vulnerabilityCriteria[] = $vulnerabilityCriterion;

        return $this;
    }

    /**
     * Remove vulnerabilityCriterion.
     *
     * @param VulnerabilityCriterion $vulnerabilityCriterion
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
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
     * @deprecated
     * @param Phone $phone
     *
     * @return Beneficiary
     */
    public function addPhone(Phone $phone): self
    {
        $this->person->addPhone($phone);

        return $this;
    }

    /**
     * Remove phone.
     * @deprecated
     * @param Phone $phone
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePhone(Phone $phone): bool
    {
        return $this->person->removePhone($phone);
    }

    /**
     * Get phones.
     * @deprecated
     * @SymfonyGroups({"FullHousehold", "SmallHousehold","FullBeneficiary"})
     * @return Collection
     */
    public function getPhones(): Collection
    {
        return $this->person->getPhones();
    }

    /**
     * Set phones.
     * @deprecated
     * @param $collection
     *
     * @return Beneficiary
     */
    public function setPhones(Collection $collection = null): self
    {
        $this->person->setPhones($collection);

        return $this;
    }

    /**
     * Set nationalId.
     * @deprecated
     * @param  $collection
     *
     * @return Beneficiary
     */
    public function setNationalIds(Collection $collection = null): self
    {
        $this->person->setNationalIds($collection);

        return $this;
    }

    /**
     * Add nationalId.
     * @deprecated
     * @param NationalId $nationalId
     *
     * @return Beneficiary
     */
    public function addNationalId(NationalId $nationalId): self
    {
        $this->person->addNationalId($nationalId);

        return $this;
    }

    /**
     * Remove nationalId.
     * @deprecated
     * @param NationalId $nationalId
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeNationalId(NationalId $nationalId): bool
    {
        return $this->person->removeNationalId($nationalId);
    }

    /**
     * Get nationalIds.
     * @deprecated
     * @SymfonyGroups({"FullHousehold", "SmallHousehold","FullBeneficiary"})
     * @return Collection
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
     */
    public function getStatus(): bool
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
    public function setResidencyStatus($residencyStatus): self
    {
        $this->residencyStatus = $residencyStatus;
        return $this;
    }

    /**
     * Set profile.
     * @deprecated
     * @param Profile|null $profile
     *
     * @return Beneficiary
     */
    public function setProfile(Profile $profile = null): self
    {
        $this->person->setProfile($profile);

        return $this;
    }

    /**
     * Get profile.
     * @deprecated
     * @SymfonyGroups({"FullHousehold", "SmallHousehold","FullBeneficiary"})
     * @return Profile|null
     */
    public function getProfile(): ?Profile
    {
        return $this->person->getProfile();
    }

    /**
     * Set referral.
     * @deprecated
     * @param Referral|null $referral
     *
     * @return Beneficiary
     */
    public function setReferral(Referral $referral = null)
    {
        $this->person->setReferral($referral);

        return $this;
    }

    /**
     * Get referral.
     * @deprecated
     * @SymfonyGroups({"FullHousehold", "SmallHousehold","FullBeneficiary"})
     * @return Referral|null
     */
    public function getReferral(): ?Referral
    {
        return $this->person->getReferral();
    }


    /**
     * Returns an array representation of this class in order to prepare the export
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

        // Recover nationalID from nationalID object
        $typenationalID = [];
        $valuesnationalID = [];
        foreach ($this->getNationalIds()->getValues() as $value) {
            array_push($typenationalID, $value->getIdType());
            array_push($valuesnationalID, $value->getIdNumber());
        }
        $typenationalID = join(',', $typenationalID);
        $valuesnationalID = join(',', $valuesnationalID);

        //Recover country specifics for the household
        $valueCountrySpecific = [];
        foreach ($this->getHousehold()->getCountrySpecificAnswers()->getValues() as $value) {
            $valueCountrySpecific[$value->getCountrySpecific()->getFieldString()] = $value->getAnswer();
        }

        if ($this->getGender() == 0) {
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
        $beneficiaryFields = $this->getCommonBeneficiaryExportFields();

        if ($this->status === true) {
            $finalArray = array_merge(
                ["household ID" => $this->getHousehold()->getId()],
                $householdFields,
                ["adm1" => $adm1,
                    "adm2" => $adm2,
                    "adm3" => $adm3,
                    "adm4" => $adm4]
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

        $tempBenef = [
            "beneficiary ID" => $this->getId(),
            "localGivenName" => $this->getLocalGivenName(),
            "localFamilyName" => $this->getLocalFamilyName(),
            "enGivenName" => $this->getEnGivenName(),
            "enFamilyName" => $this->getEnFamilyName(),
            "gender" => $valueGender,
            "head" => $this->getStatus() === true ? "true" : "false",
            "residencyStatus" => $this->getResidencyStatus(),
            "dateOfBirth" => $this->getDateOfBirth()->format('d-m-Y'),
            "vulnerabilityCriteria" => $valuescriteria,
            "type phone 1" => $typephones[0],
            "prefix phone 1" => $prefixphones[0],
            "phone 1" => $valuesphones[0],
            "proxy phone 1" => $proxyphones[0],
            "type phone 2" => $typephones[1],
            "prefix phone 2" => $prefixphones[1],
            "phone 2" => $valuesphones[1],
            "proxy phone 2" => $proxyphones[1],
            "ID Type" => $typenationalID,
            "ID Number" => $valuesnationalID,
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
        if ($this->getGender() == 0) {
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
            "Date Of Birth" => $this->getDateOfBirth()->format('d-m-Y'),
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
            $livelihood = Household::LIVELIHOOD[$this->getHousehold()->getLivelihood()];
        }

        $assets = array_map(function ($value) {
            return Household::ASSETS[$value];
        }, (array) $this->getHousehold()->getAssets());

        $shelterStatus = null;
        if (null !== $this->getHousehold()->getShelterStatus()) {
            $shelterStatus = Household::SHELTER_STATUSES[$this->getHousehold()->getShelterStatus()];
        }

        $supportReceivedTypes = array_map(function ($value) {
            return Household::SUPPORT_RECIEVED_TYPES[$value];
        }, (array) $this->getHousehold()->getSupportReceivedTypes());

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
            "incomeLevel" => $this->getHousehold()->getIncomeLevel(),
            "foodConsumptionScore" => $this->getHousehold()->getFoodConsumptionScore(),
            "copingStrategiesIndex" => $this->getHousehold()->getCopingStrategiesIndex(),
            "notes" => $this->getHousehold()->getNotes(),
            "latitude" => $this->getHousehold()->getLatitude(),
            "longitude" => $this->getHousehold()->getLongitude(),
            "Assets" => implode(', ', $assets),
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
            "Referral Comment" => $referral_comment
        ];

        return array_merge($this->getCommonHouseholdExportFields(), $this->getCommonBeneficiaryExportFields(), $referralInfo);
    }

    /**
     * Returns age of beneficiary in years
     * @return int|null
     */
    public function getAge(): ?int
    {
        if ($this->getDateOfBirth()) {
            try {
                return $this->getDateOfBirth()->diff(new DateTime('now'))->y;
            } catch (Exception $ex) {
                return null;
            }
        }

        return null;
    }

    /**
     * @SymfonyGroups({"FullHousehold"})
     * @return string
     */
    public function getSmartcard()
    {
        foreach ($this->smartcards as $smartcard) {
            if ($smartcard->isActive()) {
                $this->smartcard = $smartcard->getSerialNumber();
                return $this->smartcard;
            }
        }

        return null;
    }
}
