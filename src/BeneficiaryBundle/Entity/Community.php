<?php

namespace BeneficiaryBundle\Entity;

use CommonBundle\Entity\Location;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * Community
 *
 * @ORM\Table(name="community")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\CommunityRepository")
 */
class Community
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_name", type="string", length=255, nullable=true)
     * @Groups({"FullBeneficiary", "FullCommunity"})
     */
    private $contactName;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_family_name", type="string", length=255, nullable=true)
     * @Groups({"FullBeneficiary", "FullCommunity"})
     */
    private $contactFamilyName;

    /**
     * @var string
     *
     * @ORM\Column(name="phone_number", type="string", length=45, nullable=true)
     * @Groups({"FullBeneficiary", "FullCommunity", "FullHousehold", "FullReceivers", "ValidatedDistribution"})
     */
    private $phoneNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="phone_prefix", type="string", length=45, nullable=true)
     * @Groups({"FullBeneficiary", "FullCommunity", "FullHousehold", "FullReceivers", "ValidatedDistribution"})
     */
    private $phonePrefix;

    /**
     * @var NationalId
     *
     * @ORM\OneToOne(targetEntity="BeneficiaryBundle\Entity\NationalId", cascade={"persist", "remove"})
     * @Groups({"FullInstitution", "FullBeneficiary", "FullCommunity", "FullHousehold", "SmallHousehold", "FullReceivers"})
     */
    private $nationalId;

    /**
     * @ORM\OneToOne(targetEntity="BeneficiaryBundle\Entity\Address", cascade={"persist", "remove"})
     * @Groups({"FullBeneficiary", "FullCommunity"})
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="latitude", type="string", length=45, nullable=true)
     * @Groups({"FullBeneficiary", "FullCommunity"})
     */
    private $latitude;

    /**
     * @var string
     *
     * @ORM\Column(name="longitude", type="string", length=45, nullable=true)
     * @Groups({"FullBeneficiary", "FullCommunity"})
     */
    private $longitude;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $archived = 0;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        $names = [];
        if (!$this->getAddress()
            || !$this->getAddress()->getLocation()
            || !$this->getAddress()->getLocation()->getAdm1()) {
            return "global community";
        }
        $names[] = $this->getAddress()->getLocation()->getAdm1()->getName();
        if ($this->getAddress()->getLocation()->getAdm2())
        {
            $names[] = $this->getAddress()->getLocation()->getAdm2()->getName();
        }
        if ($this->getAddress()->getLocation()->getAdm3())
        {
            $names[] = $this->getAddress()->getLocation()->getAdm3()->getName();
        }
        if ($this->getAddress()->getLocation()->getAdm3())
        {
            $names[] = $this->getAddress()->getLocation()->getAdm3()->getName();
        }
        if ($this->getAddress()->getLocation()->getAdm4())
        {
            $names[] = $this->getAddress()->getLocation()->getAdm4()->getName();
        }

        return implode(' ', $names);
    }

    /**
     * @return string|null
     */
    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    /**
     * @param string|null $contactName
     */
    public function setContactName(?string $contactName): void
    {
        $this->contactName = $contactName;
    }

    /**
     * @return string
     */
    public function getContactFamilyName(): string
    {
        return $this->contactFamilyName;
    }

    /**
     * @param string $contactFamilyName
     */
    public function setContactFamilyName(string $contactFamilyName): void
    {
        $this->contactFamilyName = $contactFamilyName;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @param string|null $phoneNumber
     */
    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return string|null
     */
    public function getPhonePrefix(): ?string
    {
        return $this->phonePrefix;
    }

    /**
     * @param string|null $phonePrefix
     */
    public function setPhonePrefix(?string $phonePrefix): void
    {
        $this->phonePrefix = $phonePrefix;
    }

    /**
     * @return NationalId|null
     */
    public function getNationalId(): ?NationalId
    {
        return $this->nationalId;
    }

    /**
     * @param NationalId|null $nationalId
     */
    public function setNationalId(?NationalId $nationalId): void
    {
        $this->nationalId = $nationalId;
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

    /**
     * Set archived.
     *
     * @param bool $archived
     *
     * @return self
     */
    public function setArchived(bool $archived)
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Get archived.
     *
     * @return bool
     */
    public function getArchived(): bool
    {
        return $this->archived;
    }

}
