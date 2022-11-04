<?php

namespace Entity;

use DateTime;
use DateTimeInterface;
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
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id;

    /**
     * @ORM\Column(name="enGivenName", type="string", length=255, nullable=true)
     */
    private ?string $enGivenName = null;

    /**
     * @ORM\Column(name="enFamilyName", type="string", length=255, nullable=true)
     */
    private ?string $enFamilyName = null;

    /**
     * @ORM\Column(name="localGivenName", type="string", length=255, nullable=true)
     */
    #[Assert\NotBlank(message: 'The local given name is required.')]
    private ?string $localGivenName = null;

    /**
     * @ORM\Column(name="localFamilyName", type="string", length=255, nullable=true)
     */
    #[Assert\NotBlank(message: 'The local family name is required.')]
    private ?string $localFamilyName = null;

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
     */
    #[Assert\NotBlank(message: 'The date of birth is required.')]
    private $dateOfBirth;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="updated_on", type="datetime", nullable=true)
     */
    private $updatedOn;

    /**
     * @ORM\OneToOne(targetEntity="Entity\Profile", cascade={"persist", "remove"})
     */
    private ?\Entity\Profile $profile = null;

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
     * @ORM\OneToOne(targetEntity="Entity\Referral", cascade={"persist", "remove"})
     */
    private ?\Entity\Referral $referral = null;

    /**
     * @ORM\Column(name="local_parents_name", type="string", length=255, nullable=true)
     */
    private ?string $localParentsName = null;

    /**
     * @ORM\Column(name="en_parents_name", type="string", length=255, nullable=true)
     */
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

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set enGivenName.
     *
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
    public function setEnFamilyName(?string $enFamilyName)
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
    public function setLocalGivenName(?string $localGivenName)
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
    public function setLocalFamilyName(?string $localFamilyName)
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
     * @see PersonGender::values()
     *
     */
    public function setGender(?string $gender)
    {
        self::validateValue('gender', PersonGender::class, $gender, true);
        $this->gender = PersonGenderEnum::valueToDB($gender);

        return $this;
    }

    /**
     * Get gender.
     *
     * @see PersonGender::values()
     *
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
    public function setDateOfBirth(?DateTimeInterface $dateOfBirth)
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
    public function setUpdatedOn(?DateTimeInterface $updatedOn = null)
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
    public function addPhone(Phone $phone)
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
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeNationalId(NationalId $nationalId)
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
