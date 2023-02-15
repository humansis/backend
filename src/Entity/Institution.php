<?php

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Institution
 */
#[ORM\Table(name: 'institution')]
#[ORM\Entity(repositoryClass: 'Repository\InstitutionRepository')]
class Institution extends AbstractBeneficiary
{
    final public const TYPE_SCHOOL = 'school';
    final public const TYPE_HEALTH_CENTER = 'health';
    final public const TYPE_COMMUNITY_CENTER = 'community_center';
    final public const TYPE_GOVERNMENT = 'government';
    final public const TYPE_PRODUCTION = 'production';
    final public const TYPE_COMMERCE = 'commerce';
    final public const TYPE_ALL = [
        self::TYPE_SCHOOL,
        self::TYPE_HEALTH_CENTER,
        self::TYPE_COMMUNITY_CENTER,
        self::TYPE_GOVERNMENT,
        self::TYPE_PRODUCTION,
        self::TYPE_COMMERCE,
    ];

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    #[Assert\Choice(choices: Institution::TYPE_ALL)]
    #[ORM\Column(name: 'type', type: 'string', length: 255)]
    private ?string $type = null;

    #[ORM\OneToOne(targetEntity: 'Entity\Person', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'contact_person_id', referencedColumnName: 'id', nullable: true)]
    private ?\Entity\Person $contact;

    #[ORM\OneToOne(targetEntity: 'Entity\Address', cascade: ['persist', 'remove'])]
    private $address;

    #[ORM\Column(name: 'latitude', type: 'string', length: 45, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(name: 'longitude', type: 'string', length: 45, nullable: true)]
    private ?string $longitude = null;

    /**
     * Institution constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->contact = new Person();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Set type.
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Get type.
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    public function getContact(): ?Person
    {
        return $this->contact;
    }

    public function setContact(?Person $contact): void
    {
        $this->contact = $contact;
    }

    public function getContactName(): ?string
    {
        return $this->contact->getEnGivenName();
    }

    public function setContactName(?string $contactName): void
    {
        $this->contact->setEnGivenName($contactName);
    }

    public function getContactFamilyName(): ?string
    {
        return $this->contact->getEnFamilyName();
    }

    public function setContactFamilyName(?string $contactFamilyName): void
    {
        $this->contact->setEnFamilyName($contactFamilyName);
    }

    public function getPhone(): ?Phone
    {
        if ($this->contact->getPhones()->count() === 0) {
            return null;
        }

        return $this->contact->getPhones()->current();
    }

    public function setPhone(?Phone $phone): void
    {
        if ($phone) {
            $phone->setPerson($this->getContact());
            $this->contact->setPhones(new ArrayCollection([$phone]));
        } else {
            $this->contact->setPhones(new ArrayCollection());
        }
    }

    public function getPhoneNumber(): ?string
    {
        if ($this->getPhone()) {
            return $this->getPhone()->getNumber();
        }

        return null;
    }

    public function getPhonePrefix(): ?string
    {
        if (!$this->getPhone()) {
            return null;
        }

        return $this->getPhone()->getPrefix();
    }

    public function getNationalId(): ?NationalId
    {
        if ($this->contact->getNationalIds()->count() === 0) {
            return null;
        }

        return $this->contact->getNationalIds()->current();
    }

    public function setNationalId(?NationalId $nationalId): void
    {
        if ($nationalId) {
            $nationalId->setPerson($this->getContact());
            $this->contact->setNationalIds(new ArrayCollection([$nationalId]));
        } else {
            $this->contact->setNationalIds(new ArrayCollection());
        }
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    /**
     * @param Address|null $address
     */
    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    /**
     * Set lat.
     *
     *
     * @return self
     */
    public function setLatitude(?string $latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get lat.
     */
    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    /**
     * Set long.
     *
     *
     * @return self
     */
    public function setLongitude(?string $longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }
}
