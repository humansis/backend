<?php

namespace Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;

use DBAL\PersonGenderEnum;
use Entity\Helper\EnumTrait;
use Enum\PersonGender;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Person
 *
 * @ORM\Table(name="person", indexes={@ORM\Index(name="idx_local_name", columns={"localGivenName", "localFamilyName"})})
 * @ORM\Entity()
 */
class Person
{
    use EnumTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="enGivenName", type="string", length=255, nullable=true)
     */
    private $enGivenName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="enFamilyName", type="string", length=255, nullable=true)
     */
    private $enFamilyName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="localGivenName", type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="The local given name is required.")
     */
    private $localGivenName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="localFamilyName", type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="The local family name is required.")
     */
    private $localFamilyName;

    /**
     * @var int|null
     *
     * @ORM\Column(name="gender", type="smallint", nullable=true)
     */
    private $gender;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="dateOfBirth", type="date", nullable=true)
     * @Assert\NotBlank(message="The date of birth is required.")
     */
    private $dateOfBirth;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="updated_on", type="datetime", nullable=true)
     */
    private $updatedOn;

    /**
     * @var Profile|null
     * @ORM\OneToOne(targetEntity="Entity\Profile", cascade={"persist", "remove"})
     */
    private $profile;

    /**
     * @var Phone[]|Collection
     * @ORM\OneToMany(targetEntity="Entity\Phone", mappedBy="person", cascade={"persist", "remove"})
     */
    private $phones;

    /**
     * @var NationalId[]|Collection
     * @ORM\OneToMany(targetEntity="Entity\NationalId", mappedBy="person", cascade={"persist", "remove"})
     */
    private $nationalIds;

    /**
     * @var Referral|null
     * @ORM\OneToOne(targetEntity="Entity\Referral", cascade={"persist", "remove"})
     */
    private $referral;

    /**
     * @var string|null
     *
     * @ORM\Column(name="local_parents_name", type="string", length=255, nullable=true)
     */
    private $localParentsName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="en_parents_name", type="string", length=255, nullable=true)
     */
    private $enParentsName;

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
    public function setEnGivenName(?string $enGivenName): Person
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
    public function setEnFamilyName(?string $enFamilyName): Person
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
    public function setLocalGivenName(?string $localGivenName): Person
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
    public function setLocalFamilyName(?string $localFamilyName): Person
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
     * @see PersonGender::values()
     *
     * @param string|null $gender
     *
     * @return self
     */
    public function setGender(?string $gender): Person
    {
        self::validateValue('gender', PersonGender::class, $gender, true);
        $this->gender = PersonGenderEnum::valueToDB($gender);

        return $this;
    }

    /**
     * Get gender.
     * @see PersonGender::values()
     *
     * @return string|null
     */
    public function getGender(): ?string
    {
        return PersonGenderEnum::valueFromDB($this->gender);
    }

    /**
     * Set dateOfBirth.
     *
     * @param \DateTimeInterface|null $dateOfBirth
     *
     * @return self
     */
    public function setDateOfBirth(?\DateTimeInterface $dateOfBirth): Person
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
    public function setUpdatedOn(?\DateTimeInterface $updatedOn = null): Person
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
    public function addPhone(Phone $phone): Person
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
    public function removePhone(Phone $phone): bool
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
     * @param Collection|null $collection
     *
     * @return self
     */
    public function setPhones(?Collection $collection = null): Person
    {
        $this->phones = $collection;

        return $this;
    }

    /**
     * @return Phone|null
     */
    public function getFirstNoProxyPhone(): ?Phone
    {
        if($this->phones->count() === 0) {
            return null;
        }

        foreach($this->phones as $phone) {
            if(!$phone->getProxy()) {
                return $phone;
            }
        }

        return null;
    }

    /**
     * Set nationalId.
     *
     * @param Collection|null $collection
     *
     * @return self
     */
    public function setNationalIds(?Collection $collection = null): Person
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
    public function addNationalId(NationalId $nationalId): Person
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
    public function removeNationalId(NationalId $nationalId): bool
    {
        return $this->nationalIds->removeElement($nationalId);
    }

    /**
     * @return NationalId|null
     */
    public function getPrimaryIdType(): ?NationalId
    {
        $min = null;
        $primaryNationalId = null;
        foreach ($this->getNationalIds() as $nationalId) {
            if (is_null($min) || $nationalId->getPriority() < $min) {
                $min = $nationalId->getPriority();
                $primaryNationalId = $nationalId;
            }
        }

        return $primaryNationalId;
    }

    /**
     * Get nationalIds.
     *
     * @return NationalId[]
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
    public function setProfile(Profile $profile = null): Person
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * Get profile.
     *
     * @return Profile|null
     */
    public function getProfile(): ?Profile
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
    public function setReferral(Referral $referral = null): Person
    {
        $this->referral = $referral;

        return $this;
    }

    /**
     * Get referral.
     *
     * @return Referral|null
     */
    public function getReferral(): ?Referral
    {
        return $this->referral;
    }

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

}
