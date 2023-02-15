<?php

namespace Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;
use Exception;
use DBAL\PersonGenderEnum;
use Entity\Helper\EnumTrait;
use Enum\PersonGender;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Person
 */
#[ORM\Table(name: 'person')]
#[ORM\Index(columns: ['localGivenName', 'localFamilyName'], name: 'idx_local_name')]
#[ORM\Entity]
class Person
{
    use EnumTrait;
    use StandardizedPrimaryKey;

    #[ORM\Column(name: 'enGivenName', type: 'string', length: 255, nullable: true)]
    private ?string $enGivenName = null;

    #[ORM\Column(name: 'enFamilyName', type: 'string', length: 255, nullable: true)]
    private ?string $enFamilyName = null;

    #[Assert\NotBlank(message: 'The local given name is required.')]
    #[ORM\Column(name: 'localGivenName', type: 'string', length: 255, nullable: true)]
    private ?string $localGivenName = null;

    #[Assert\NotBlank(message: 'The local family name is required.')]
    #[ORM\Column(name: 'localFamilyName', type: 'string', length: 255, nullable: true)]
    private ?string $localFamilyName = null;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'gender', type: 'smallint', nullable: true)]
    private $gender;

    /**
     * @var DateTime|null
     */
    #[Assert\NotBlank(message: 'The date of birth is required.')]
    #[ORM\Column(name: 'dateOfBirth', type: 'date', nullable: true)]
    private $dateOfBirth;

    /**
     * @var DateTime|null
     */
    #[ORM\Column(name: 'updated_on', type: 'datetime', nullable: true)]
    private $updatedOn;

    #[ORM\OneToOne(targetEntity: 'Entity\Profile', cascade: ['persist', 'remove'])]
    private ?\Entity\Profile $profile = null;

    /**
     * @var Phone[]|Collection
     */
    #[ORM\OneToMany(mappedBy: 'person', targetEntity: 'Entity\Phone', cascade: ['persist', 'remove'])]
    private $phones;

    /**
     * @var NationalId[]|Collection
     */
    #[ORM\OneToMany(mappedBy: 'person', targetEntity: 'Entity\NationalId', cascade: ['persist', 'remove'])]
    private $nationalIds;

    #[ORM\OneToOne(targetEntity: 'Entity\Referral', cascade: ['persist', 'remove'])]
    private ?\Entity\Referral $referral = null;

    #[ORM\Column(name: 'local_parents_name', type: 'string', length: 255, nullable: true)]
    private ?string $localParentsName = null;

    #[ORM\Column(name: 'en_parents_name', type: 'string', length: 255, nullable: true)]
    private ?string $enParentsName = null;

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
     * Set enGivenName.
     *
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
     */
    public function getEnGivenName(): ?string
    {
        return $this->enGivenName;
    }

    /**
     * Set enFamilyName.
     *
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
     */
    public function getEnFamilyName(): ?string
    {
        return $this->enFamilyName;
    }

    /**
     * Set localGivenName.
     *
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
     */
    public function getLocalGivenName(): ?string
    {
        return $this->localGivenName;
    }

    /**
     * Set localFamilyName.
     *
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
     */
    public function getLocalFamilyName(): ?string
    {
        return $this->localFamilyName;
    }

    /**
     * Set gender.
     *
     *
     * @return self
     * @throws Exception
     * @see PersonGender::values()
     */
    public function setGender(?string $gender): Person
    {
        self::validateValue('gender', PersonGender::class, $gender, true);
        $this->gender = PersonGenderEnum::valueToDB($gender);

        return $this;
    }

    /**
     * Get gender.
     *
     * @see PersonGender::values()
     */
    public function getGender(): ?string
    {
        return PersonGenderEnum::valueFromDB($this->gender);
    }

    /**
     * Set dateOfBirth.
     *
     *
     * @return self
     */
    public function setDateOfBirth(?DateTimeInterface $dateOfBirth): Person
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    /**
     * Get dateOfBirth.
     *
     * @return DateTime|null
     */
    public function getDateOfBirth(): ?DateTimeInterface
    {
        return $this->dateOfBirth;
    }

    /**
     * Set updatedOn.
     *
     *
     * @return self
     */
    public function setUpdatedOn(?DateTimeInterface $updatedOn = null): Person
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }

    /**
     * Get updatedOn.
     *
     * @return DateTime|null
     */
    public function getUpdatedOn(): ?DateTimeInterface
    {
        return $this->updatedOn;
    }

    /**
     * Add phone.
     *
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
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
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

    public function getFirstPhoneWithPrefix(): ?string
    {
        /**
         * @var Phone|null $phone
         */
        $phone = $this->phones->first();
        if ($phone) {
            return $phone->getPrefix() . ' ' . $phone->getNumber();
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
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeNationalId(NationalId $nationalId): bool
    {
        return $this->nationalIds->removeElement($nationalId);
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
     * @return NationalId|null
     */
    public function getPrimaryNationalId(): ?NationalId
    {
        $minPriority = null;
        $primaryNationalId = null;
        foreach ($this->nationalIds as $nationalId) {
            if (!$minPriority) {
                $minPriority = $nationalId->getPriority();
                $primaryNationalId = $nationalId;
            }
            if ($nationalId->getPriority() < $minPriority) {
                $minPriority = $nationalId->getPriority();
                $primaryNationalId = $nationalId;
            }
        }

        return $primaryNationalId;
    }

    /**
     * @return NationalId|null
     */
    public function getSecondaryNationalId(): ?NationalId
    {
        $secondary = null;
        $primary = $this->getPrimaryNationalId();
        if (!$primary) {
            return null;
        }

        $minPriority = $primary->getPriority() + 1;
        foreach ($this->nationalIds as $nationalId) {
            if ($nationalId->getId() === $primary->getId()) {
                continue;
            }
            if ($nationalId->getPriority() > $primary->getPriority()) {
                if (!$secondary) {
                    $minPriority = $nationalId->getPriority();
                    $secondary = $nationalId;
                } elseif ($nationalId->getPriority() < $minPriority) {
                    $minPriority = $nationalId->getPriority();
                    $secondary = $nationalId;
                }
            }
        }

        return $secondary;
    }

    /**
     * @return NationalId|null
     */
    public function getTertiaryNationalId(): ?NationalId
    {
        $tertiary = null;
        $primary = $this->getPrimaryNationalId();
        $secondary = $this->getSecondaryNationalId();
        if (!$primary || !$secondary) {
            return null;
        }

        $minPriority = $secondary->getPriority() + 1;
        foreach ($this->nationalIds as $nationalId) {
            if ($nationalId->getId() === $primary->getId() || $nationalId->getId() === $secondary->getId()) {
                continue;
            }
            if ($nationalId->getPriority() > $secondary->getPriority()) {
                if (!$tertiary) {
                    $minPriority = $nationalId->getPriority();
                    $tertiary = $nationalId;
                } elseif ($nationalId->getPriority() < $minPriority) {
                    $minPriority = $nationalId->getPriority();
                    $tertiary = $nationalId;
                }
            }
        }

        return $tertiary;
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
     */
    public function getAge(): ?int
    {
        if ($this->getDateOfBirth()) {
            try {
                return $this->getDateOfBirth()->diff(new DateTime('now'))->y;
            } catch (Exception) {
                return null;
            }
        }

        return null;
    }

    public function setLocalParentsName(?string $localParentsName): Person
    {
        $this->localParentsName = $localParentsName;

        return $this;
    }

    public function getLocalParentsName(): ?string
    {
        return $this->localParentsName;
    }

    public function setEnParentsName(?string $enParentsName): Person
    {
        $this->enParentsName = $enParentsName;

        return $this;
    }

    public function getEnParentsName(): ?string
    {
        return $this->enParentsName;
    }
}
