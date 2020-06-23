<?php

namespace BeneficiaryBundle\Entity;

use CommonBundle\Entity\Location;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * Institution
 *
 * @ORM\Table(name="institution")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\InstitutionRepository")
 */
class Institution
{
    const TYPE_SCHOOL = 'school';
    const TYPE_HEALTH_CENTER = 'health';
    const TYPE_COMMUNITY_CENTER = 'comunity_center';
    const TYPE_GOVERNMENT = 'government';
    const TYPE_PRODUCTION = 'production';
    const TYPE_COMMERCE = 'commerce';

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
     * @ORM\Column(name="type", type="string", length=255)
     * @Groups({"FullBeneficiary", "FullInstitution"})
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_name", type="string", length=255, nullable=true)
     * @Groups({"FullBeneficiary", "FullInstitution"})
     */
    private $contactName;

    /**
     * @var string
     *
     * @ORM\Column(name="phone_number", type="string", length=45, nullable=true)
     * @Groups({"FullBeneficiary", "FullHousehold", "FullReceivers", "ValidatedDistribution"})
     */
    private $phoneNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="phone_prefix", type="string", length=45, nullable=true)
     * @Groups({"FullBeneficiary", "FullHousehold", "FullReceivers", "ValidatedDistribution"})
     */
    private $phonePrefix;

    /**
     * @var string
     *
     * @ORM\Column(name="id_number", type="string", length=255, nullable=true)
     * @Groups({"FullBeneficiary", "FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedDistribution"})
     */
    private $idNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="id_type", type="string", length=45, nullable=true)
     * @Groups({"FullBeneficiary", "FullHousehold", "SmallHousehold", "FullReceivers"})
     */
    private $idType;

    /**
     * @ORM\OneToOne(targetEntity="BeneficiaryBundle\Entity\Address", cascade={"persist", "remove"})
     * @Groups({"FullBeneficiary", "FullInstitution"})
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="latitude", type="string", length=45, nullable=true)
     * @Groups({"FullBeneficiary", "FullInstitution"})
     */
    private $latitude;

    /**
     * @var string
     *
     * @ORM\Column(name="longitude", type="string", length=45, nullable=true)
     * @Groups({"FullBeneficiary", "FullInstitution"})
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
     * Set type.
     *
     * @param string $type
     *
     * @return Institution
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
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
     * @return string|null
     */
    public function getIdNumber(): ?string
    {
        return $this->idNumber;
    }

    /**
     * @param string|null $idNumber
     */
    public function setIdNumber(?string $idNumber): void
    {
        $this->idNumber = $idNumber;
    }

    /**
     * @return string|null
     */
    public function getIdType(): ?string
    {
        return $this->idType;
    }

    /**
     * @param string|null $idType
     */
    public function setIdType(?string $idType): void
    {
        $this->idType = $idType;
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
