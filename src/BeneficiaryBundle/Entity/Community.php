<?php

namespace BeneficiaryBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Community
 *
 * @ORM\Table(name="community")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\CommunityRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Community extends AbstractBeneficiary
{
    /**
     * @var string
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     */
    private $name;

    /**
     * @var Person|null
     * @ORM\OneToOne(targetEntity="BeneficiaryBundle\Entity\Person", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="contact_person_id", referencedColumnName="id", nullable=true)
     */
    private $contact;

    /**
     * @ORM\OneToOne(targetEntity="BeneficiaryBundle\Entity\Address", cascade={"persist", "remove"})
     * @SymfonyGroups({"FullBeneficiary", "FullCommunity"})
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="latitude", type="string", length=45, nullable=true)
     * @SymfonyGroups({"FullBeneficiary", "FullCommunity"})
     */
    private $latitude;

    /**
     * @var string
     *
     * @ORM\Column(name="longitude", type="string", length=45, nullable=true)
     * @SymfonyGroups({"FullBeneficiary", "FullCommunity"})
     */
    private $longitude;

    /**
     * Community constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->contact = new Person();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Person|null
     */
    public function getContact(): ?Person
    {
        return $this->contact;
    }

    /**
     * @param Person|null $contact
     */
    public function setContact(?Person $contact): void
    {
        $this->contact = $contact;
    }

    /**
     * @return string|null
     */
    public function getContactName(): ?string
    {
        return $this->contact->getEnGivenName();
    }

    /**
     * @param string|null $contactName
     */
    public function setContactName(?string $contactName): void
    {
        $this->contact->setEnGivenName($contactName);
    }

    /**
     * @return string|null
     */
    public function getContactFamilyName(): ?string
    {
        return $this->contact->getEnFamilyName();
    }

    /**
     * @param string|null $contactFamilyName
     */
    public function setContactFamilyName(?string $contactFamilyName): void
    {
        $this->contact->setEnFamilyName($contactFamilyName);
    }

    /**
     * @return Phone|null
     */
    public function getPhone(): ?Phone
    {
        if ($this->contact->getPhones()->count() === 0) return null;
        return $this->contact->getPhones()->current();
    }

    /**
     * @param Phone|null $phone
     */
    public function setPhone(?Phone $phone): void
    {
        if ($phone) {
            $phone->setPerson($this->getContact());
            $this->contact->setPhones(new ArrayCollection([$phone]));
        } else {
            $this->contact->setPhones(new ArrayCollection());
        }
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        if ($this->getPhone()) {
            return $this->getPhone()->getNumber();
        }
        return null;
    }

    /**
     * @return string|null
     */
    public function getPhonePrefix(): ?string
    {
        if (!$this->getPhone()) {
            return null;
        }
        return $this->getPhone()->getPrefix();
    }

    /**
     * @return NationalId|null
     */
    public function getNationalId(): ?NationalId
    {
        if ($this->contact->getNationalIds()->count() === 0) return null;
        return $this->contact->getNationalIds()->current();
    }

    /**
     * @param NationalId|null $nationalId
     */
    public function setNationalId(?NationalId $nationalId): void
    {
        if ($nationalId) {
            $nationalId->setPerson($this->getContact());
            $this->contact->setNationalIds(new ArrayCollection([$nationalId]));
        } else {
            $this->contact->setNationalIds(new ArrayCollection());
        }
    }

    /**
     * @return Address|null
     */
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
     * @param string|null $latitude
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
     *
     * @return string|null
     */
    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    /**
     * Set long.
     *
     * @param string|null $longitude
     *
     * @return self
     */
    public function setLongitude(?string $longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLongitude(): ?string
    {
        return $this->longitude;
    }
}
