<?php

namespace BeneficiaryBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;

use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Person
 *
 * @ORM\Table(name="person", indexes={@ORM\Index(name="idx_local_name", columns={"localGivenName", "localFamilyName"})})
 * @ORM\Entity()
 */
class Person
{
    const GENDER_FEMALE = 0;
    const GENDER_MALE = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedAssistance", "FullProject", "FullBeneficiary", "SmartcardOverview", "FullSmartcard"})
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="enGivenName", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedAssistance", "FullBooklet", "FullBeneficiary"})
     */
    private $enGivenName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="enFamilyName", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedAssistance", "FullBeneficiary"})
     */
    private $enFamilyName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="localGivenName", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedAssistance", "FullBooklet", "FullBeneficiary"})
     * @Assert\NotBlank(message="The local given name is required.")
     */
    private $localGivenName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="localFamilyName", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedAssistance", "FullBeneficiary"})
     * @Assert\NotBlank(message="The local family name is required.")
     */
    private $localFamilyName;

    /**
     * @var int|null
     *
     * @ORM\Column(name="gender", type="smallint", nullable=true)
     * @SymfonyGroups({"FullHousehold", "FullReceivers", "ValidatedAssistance", "FullBeneficiary"})
     * @Assert\NotBlank(message="The gender is required.")
     */
    private $gender;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="dateOfBirth", type="date", nullable=true)
     * @SymfonyGroups({"FullHousehold", "FullReceivers", "ValidatedAssistance", "FullBeneficiary"})
     * @Assert\NotBlank(message="The date of birth is required.")
     */
    private $dateOfBirth;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="updated_on", type="datetime", nullable=true)
     * @SymfonyGroups({"FullHousehold", "FullBeneficiary"})
     */
    private $updatedOn;

    /**
     * @var Profile|null
     * @ORM\OneToOne(targetEntity="BeneficiaryBundle\Entity\Profile", cascade={"persist", "remove"})
     * @SymfonyGroups({"FullHousehold", "FullBeneficiary"})
     */
    private $profile;

    /**
     * @var Phone[]|Collection
     * @ORM\OneToMany(targetEntity="BeneficiaryBundle\Entity\Phone", mappedBy="person", cascade={"persist", "remove"})
     * @SymfonyGroups({"FullHousehold", "FullReceivers", "ValidatedAssistance", "FullBeneficiary"})
     */
    private $phones;

    /**
     * @var NationalId[]|Collection
     * @ORM\OneToMany(targetEntity="BeneficiaryBundle\Entity\NationalId", mappedBy="person", cascade={"persist", "remove"})
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedAssistance", "FullBeneficiary"})
     */
    private $nationalIds;

    /**
     * @var Referral|null
     * @ORM\OneToOne(targetEntity="BeneficiaryBundle\Entity\Referral", cascade={"persist", "remove"})
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "ValidatedAssistance", "FullBeneficiary"})
     */
    private $referral;

    /**
     * @var string|null
     *
     * @ORM\Column(name="local_parents_name", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedAssistance", "FullBooklet", "FullBeneficiary"})
     */
    private $localParentsName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="en_parents_name", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedAssistance", "FullBooklet", "FullBeneficiary"})
     */
    private $enParentsName;

    /**
     * @var Beneficiary|null
     *
     * @ORM\OneToOne(targetEntity="BeneficiaryBundle\Entity\Beneficiary", mappedBy="person")
     */
    private $beneficiary;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->phones = new ArrayCollection();
        $this->nationalIds = new ArrayCollection();
        $this->setUpdatedOn(new DateTime());

        //TODO check if updatedOn everytime
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }


    /**
     * Set enGivenName.
     *
     * @param string|null $enGivenName
     *
     * @return self
     */
    public function setEnGivenName(?string $enGivenName)
    {
        $this->enGivenName = $enGivenName;

        return $this;
    }

    /**
     * Get enGivenName.
     *
     * @return string|null
     */
    public function getEnGivenName(): ?string
    {
        return $this->enGivenName;
    }

    /**
     * Set enFamilyName.
     *
     * @param string|null $enFamilyName
     *
     * @return self
     */
    public function setEnFamilyName(?string $enFamilyName)
    {
        $this->enFamilyName = $enFamilyName;

        return $this;
    }

    /**
     * Get enFamilyName.
     *
     * @return string|null
     */
    public function getEnFamilyName(): ?string
    {
        return $this->enFamilyName;
    }

    /**
     * Set localGivenName.
     *
     * @param string|null $localGivenName
     *
     * @return self
     */
    public function setLocalGivenName(?string $localGivenName)
    {
        $this->localGivenName = $localGivenName;

        return $this;
    }

    /**
     * Get localGivenName.
     *
     * @return string|null
     */
    public function getLocalGivenName(): ?string
    {
        return $this->localGivenName;
    }

    /**
     * Set localFamilyName.
     *
     * @param string|null $localFamilyName
     *
     * @return self
     */
    public function setLocalFamilyName(?string $localFamilyName)
    {
        $this->localFamilyName = $localFamilyName;

        return $this;
    }

    /**
     * Get localFamilyName.
     *
     * @return string|null
     */
    public function getLocalFamilyName(): ?string
    {
        return $this->localFamilyName;
    }

    /**
     * Set gender.
     *
     * @param int|null $gender one of self::GENDER_*
     *
     * @return self
     */
    public function setGender(?int $gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender.
     *
     * @return int|null one of self::GENDER_*
     */
    public function getGender(): ?int
    {
        return $this->gender;
    }

    /**
     * Set dateOfBirth.
     *
     * @param \DateTimeInterface|null $dateOfBirth
     *
     * @return self
     */
    public function setDateOfBirth(?\DateTimeInterface $dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    /**
     * Get dateOfBirth.
     *
     * @return DateTime|null
     */
    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->dateOfBirth;
    }

    /**
     * Set updatedOn.
     *
     * @param \DateTimeInterface|null $updatedOn
     *
     * @return self
     */
    public function setUpdatedOn(?\DateTimeInterface $updatedOn = null)
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }

    /**
     * Get updatedOn.
     *
     * @return DateTime|null
     */
    public function getUpdatedOn(): ?\DateTimeInterface
    {
        return $this->updatedOn;
    }



    /**
     * Add phone.
     *
     * @param Phone $phone
     *
     * @return self
     */
    public function addPhone(Phone $phone)
    {
        $this->phones[] = $phone;

        return $this;
    }

    /**
     * Remove phone.
     *
     * @param Phone $phone
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePhone(Phone $phone)
    {
        return $this->phones->removeElement($phone);
    }

    /**
     * Get phones.
     *
     * @return Collection
     */
    public function getPhones()
    {
        return $this->phones;
    }

    /**
     * Set phones.
     *
     * @param $collection
     *
     * @return self
     */
    public function setPhones(Collection $collection = null)
    {
        $this->phones = $collection;

        return $this;
    }

    /**
     * Set nationalId.
     *
     * @param  $collection
     *
     * @return self
     */
    public function setNationalIds(Collection $collection = null)
    {
        $this->nationalIds = $collection;

        return $this;
    }

    /**
     * Add nationalId.
     *
     * @param NationalId $nationalId
     *
     * @return self
     */
    public function addNationalId(NationalId $nationalId)
    {
        $this->nationalIds[] = $nationalId;

        return $this;
    }

    /**
     * Remove nationalId.
     *
     * @param NationalId $nationalId
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeNationalId(NationalId $nationalId)
    {
        return $this->nationalIds->removeElement($nationalId);
    }

    /**
     * Get nationalIds.
     *
     * @return Collection
     */
    public function getNationalIds()
    {
        return $this->nationalIds;
    }

    /**
     * Set profile.
     *
     * @param Profile|null $profile
     *
     * @return self
     */
    public function setProfile(Profile $profile = null)
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * Get profile.
     *
     * @return Profile|null
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * Set referral.
     *
     * @param Referral|null $referral
     *
     * @return self
     */
    public function setReferral(Referral $referral = null)
    {
        $this->referral = $referral;

        return $this;
    }

    /**
     * Get referral.
     *
     * @return Referral|null
     */
    public function getReferral()
    {
        return $this->referral;
    }


    /**
     * Returns an array representation of this class in order to prepare the export
     * @return array
     */
    // public function getMappedValueForExport(): array
    // {
    //     // Recover the phones of the self
    //     $typephones = ["", ""];
    //     $prefixphones = ["", ""];
    //     $valuesphones = ["", ""];
    //     $proxyphones = ["", ""];
    //
    //     $index = 0;
    //     foreach ($this->getPhones()->getValues() as $value) {
    //         $typephones[$index] = $value->getType();
    //         $prefixphones[$index] = $value->getPrefix();
    //         $valuesphones[$index] = $value->getNumber();
    //         $proxyphones[$index] = $value->getProxy();
    //         $index++;
    //     }
    //
    //     // Recover the  criterions from Vulnerability criteria object
    //     $valuescriteria = [];
    //     foreach ($this->getVulnerabilityCriteria()->getValues() as $value) {
    //         array_push($valuescriteria, $value->getFieldString());
    //     }
    //     $valuescriteria = join(',', $valuescriteria);
    //
    //     // Recover nationalID from nationalID object
    //     $typenationalID = [];
    //     $valuesnationalID = [];
    //     foreach ($this->getNationalIds()->getValues() as $value) {
    //         array_push($typenationalID, $value->getIdType());
    //         array_push($valuesnationalID, $value->getIdNumber());
    //     }
    //     $typenationalID = join(',', $typenationalID);
    //     $valuesnationalID = join(',', $valuesnationalID);
    //
    //     //Recover country specifics for the household
    //     $valueCountrySpecific = [];
    //     foreach ($this->getHousehold()->getCountrySpecificAnswers()->getValues() as $value) {
    //         $valueCountrySpecific[$value->getCountrySpecific()->getFieldString()] = $value->getAnswer();
    //     }
    //
    //     if ($this->getGender() == 0) {
    //         $valueGender = "Female";
    //     } else {
    //         $valueGender = "Male";
    //     }
    //
    //     $householdLocations = $this->getHousehold()->getHouseholdLocations();
    //     $currentHouseholdLocation = null;
    //     foreach ($householdLocations as $householdLocation) {
    //         if ($householdLocation->getLocationGroup() === HouseholdLocation::LOCATION_GROUP_CURRENT) {
    //             $currentHouseholdLocation = $householdLocation;
    //         }
    //     }
    //
    //     $location = $currentHouseholdLocation->getLocation();
    //
    //     $adm1 = $location->getAdm1Name();
    //     $adm2 = $location->getAdm2Name();
    //     $adm3 = $location->getAdm3Name();
    //     $adm4 = $location->getAdm4Name();
    //
    //     $householdFields = $this->getCommonHouseholdExportFields();
    //     $selfFields = $this->getCommonselfExportFields();
    //
    //     if ($this->status === true) {
    //         $finalArray = array_merge(
    //             ["household ID" => $this->getHousehold()->getId()],
    //             $householdFields,
    //             ["adm1" => $adm1,
    //                 "adm2" => $adm2,
    //                 "adm3" => $adm3,
    //                 "adm4" => $adm4]
    //         );
    //     } else {
    //         $finalArray = [
    //             "household ID" => "",
    //             "addressStreet" => "",
    //             "addressNumber" => "",
    //             "addressPostcode" => "",
    //             "camp" => "",
    //             "tent number" => "",
    //             "livelihood" => "",
    //             "incomeLevel" => "",
    //             "foodConsumptionScore" => "",
    //             "copingStrategiesIndex" => "",
    //             "notes" => "",
    //             "latitude" => "",
    //             "longitude" => "",
    //             "adm1" => "",
    //             "adm2" => "",
    //             "adm3" => "",
    //             "adm4" => "",
    //         ];
    //     }
    //
    //     $tempBenef = [
    //         "self ID" => $this->getId(),
    //         "localGivenName" => $this->getLocalGivenName(),
    //         "localFamilyName" => $this->getLocalFamilyName(),
    //         "enGivenName" => $this->getEnGivenName(),
    //         "enFamilyName" => $this->getEnFamilyName(),
    //         "gender" => $valueGender,
    //         "head" => $this->getStatus() === true ? "true" : "false",
    //         "residencyStatus" => $this->getResidencyStatus(),
    //         "dateOfBirth" => $this->getDateOfBirth()->format('d-m-Y'),
    //         "vulnerabilityCriteria" => $valuescriteria,
    //         "type phone 1" => $typephones[0],
    //         "prefix phone 1" => $prefixphones[0],
    //         "phone 1" => $valuesphones[0],
    //         "proxy phone 1" => $proxyphones[0],
    //         "type phone 2" => $typephones[1],
    //         "prefix phone 2" => $prefixphones[1],
    //         "phone 2" => $valuesphones[1],
    //         "proxy phone 2" => $proxyphones[1],
    //         "ID Type" => $typenationalID,
    //         "ID Number" => $valuesnationalID,
    //     ];
    //
    //     foreach ($valueCountrySpecific as $key => $value) {
    //         $finalArray[$key] = $value;
    //     }
    //
    //     foreach ($tempBenef as $key => $value) {
    //         $finalArray[$key] = $value;
    //     }
    //
    //     return $finalArray;
    // }
    //
    // public function getCommonselfExportFields()
    // {
    //     $gender = '';
    //     if ($this->getGender() == 0) {
    //         $gender = 'Female';
    //     } else {
    //         $gender = 'Male';
    //     }
    //
    //     return [
    //         "Local Given Name" => $this->getLocalGivenName(),
    //         "Local Family Name" => $this->getLocalFamilyName(),
    //         "English Given Name" => $this->getEnGivenName(),
    //         "English Family Name" => $this->getEnFamilyName(),
    //         "Gender" => $gender,
    //         "Date Of Birth" => $this->getDateOfBirth()->format('d-m-Y'),
    //     ];
    // }
    //
    // public function getCommonHouseholdExportFields()
    // {
    //
    //     $householdLocations = $this->getHousehold()->getHouseholdLocations();
    //     $currentHouseholdLocation = null;
    //     foreach ($householdLocations as $householdLocation) {
    //         if ($householdLocation->getLocationGroup() === HouseholdLocation::LOCATION_GROUP_CURRENT) {
    //             $currentHouseholdLocation = $householdLocation;
    //         }
    //     }
    //
    //     $camp = null;
    //     $tentNumber = null;
    //     $addressNumber = null;
    //     $addressStreet = null;
    //     $addressPostcode = null;
    //
    //     if ($currentHouseholdLocation->getType() === HouseholdLocation::LOCATION_TYPE_CAMP) {
    //         $camp = $currentHouseholdLocation->getCampAddress()->getCamp()->getName();
    //         $tentNumber = $currentHouseholdLocation->getCampAddress()->getTentNumber();
    //     } else {
    //         $addressNumber = $currentHouseholdLocation->getAddress()->getNumber();
    //         $addressStreet = $currentHouseholdLocation->getAddress()->getStreet();
    //         $addressPostcode = $currentHouseholdLocation->getAddress()->getPostcode();
    //     }
    //
    //     $livelihood = null;
    //     if (null !== $this->getHousehold()->getLivelihood()) {
    //         $livelihood = Household::LIVELIHOOD[$this->getHousehold()->getLivelihood()];
    //     }
    //
    //     $assets = array_map(function ($value) {
    //         return Household::ASSETS[$value];
    //     }, (array) $this->getHousehold()->getAssets());
    //
    //     $shelterStatus = null;
    //     if (null !== $this->getHousehold()->getShelterStatus()) {
    //         $shelterStatus = Household::SHELTER_STATUSES[$this->getHousehold()->getShelterStatus()];
    //     }
    //
    //     $supportReceivedTypes = array_map(function ($value) {
    //         return Household::SUPPORT_RECIEVED_TYPES[$value];
    //     }, (array) $this->getHousehold()->getSupportReceivedTypes());
    //
    //     $supportDateReceived = null;
    //     if (null !== $this->getHousehold()->getSupportDateReceived()) {
    //         $supportDateReceived = $this->getHousehold()->getSupportDateReceived()->format("m/d/Y");
    //     }
    //
    //     return [
    //         "addressStreet" => $addressStreet,
    //         "addressNumber" => $addressNumber,
    //         "addressPostcode" => $addressPostcode,
    //         "camp" => $camp,
    //         "tent number" => $tentNumber,
    //         "livelihood" => $livelihood,
    //         "incomeLevel" => $this->getHousehold()->getIncomeLevel(),
    //         "foodConsumptionScore" => $this->getHousehold()->getFoodConsumptionScore(),
    //         "copingStrategiesIndex" => $this->getHousehold()->getCopingStrategiesIndex(),
    //         "notes" => $this->getHousehold()->getNotes(),
    //         "latitude" => $this->getHousehold()->getLatitude(),
    //         "longitude" => $this->getHousehold()->getLongitude(),
    //         "Assets" => implode(', ', $assets),
    //         "Shelter Status" => $shelterStatus,
    //         "Debt Level" => $this->getHousehold()->getDebtLevel(),
    //         "Support Received Types" => implode(', ', $supportReceivedTypes),
    //         "Support Date Received" => $supportDateReceived,
    //     ];
    // }
    //
    // public function getCommonExportFields()
    // {
    //
    //     $referral_type = null;
    //     $referral_comment = null;
    //     if ($this->getReferral()) {
    //         $referral_type = $this->getReferral()->getType();
    //         $referral_comment = $this->getReferral()->getComment();
    //     }
    //
    //     $referralInfo = [
    //         "Referral Type" => $referral_type ? Referral::REFERRALTYPES[$referral_type] : null,
    //         "Referral Comment" => $referral_comment
    //     ];
    //
    //     return array_merge($this->getCommonHouseholdExportFields(), $this->getCommonselfExportFields(), $referralInfo);
    // }

    /**
     * Returns age of self in years
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
     * @param string|null $localParentsName
     *
     * @return Person
     */
    public function setLocalParentsName(?string $localParentsName): Person
    {
        $this->localParentsName = $localParentsName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLocalParentsName(): ?string
    {
        return $this->localParentsName;
    }


    /**
     * @param string|null $enParentsName
     *
     * @return Person
     */
    public function setEnParentsName(?string $enParentsName): Person
    {
        $this->enParentsName = $enParentsName;

        return $this;
    }


    /**
     * @return string|null
     */
    public function getEnParentsName(): ?string
    {
        return $this->enParentsName;
    }

    /**
     * @return Beneficiary|null
     */
    public function getBeneficiary(): ?Beneficiary
    {
        return $this->beneficiary;
    }

    /**
     * @param Beneficiary|null $beneficiary
     */
    public function setBeneficiary(?Beneficiary $beneficiary): void
    {
        $this->beneficiary = $beneficiary;
    }
}
