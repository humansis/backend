<?php
namespace BeneficiaryBundle\InputType;

use CommonBundle\InputType\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class NewInstitutionType implements InputTypeInterface
{
    /**
     * @var string
     * @Assert\Length(max="255")
     * @Assert\Choice(choices=BeneficiaryBundle\Entity\Institution::TYPE_ALL)
     */
    private $type;
    /**
     * @var BeneficiaryAddressType|null
     * @Assert\Valid()
     */
    private $address;
    /**
     * @var string|null
     * @Assert\Length(max="255")
     */
    private $latitude;
    /**
     * @var string|null
     * @Assert\Length(max="255")
     */
    private $longitude;
    /**
     * @var string|null
     * @Assert\Length(max="255")
     */
    private $id_type;
    /**
     * @var string|null
     * @Assert\Length(max="255")
     */
    private $id_number;
    /**
     * @var string|null
     * @Assert\Length(max="255")
     */
    private $phone_prefix;
    /**
     * @var string|null
     * @Assert\Length(max="255")
     */
    private $phone_number;
    /**
     * @var string|null
     * @Assert\Length(max="255")
     */
    private $contact_name;
    /**
     * @var string|null
     * @Assert\Length(max="255")
     */
    private $contact_family_name;

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return BeneficiaryAddressType|null
     */
    public function getAddress(): ?BeneficiaryAddressType
    {
        return $this->address;
    }

    /**
     * @param BeneficiaryAddressType|null $address
     */
    public function setAddress(?BeneficiaryAddressType $address): void
    {
        $this->address = $address;
    }

    /**
     * @return string|null
     */
    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    /**
     * @param string|null $latitude
     */
    public function setLatitude(?string $latitude): void
    {
        $this->latitude = $latitude;
    }

    /**
     * @return string|null
     */
    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    /**
     * @param string|null $longitude
     */
    public function setLongitude(?string $longitude): void
    {
        $this->longitude = $longitude;
    }

    /**
     * @return string|null
     */
    public function getIdType(): ?string
    {
        return $this->id_type;
    }

    /**
     * @param string|null $id_type
     */
    public function setIdType(?string $id_type): void
    {
        $this->id_type = $id_type;
    }

    /**
     * @return string|null
     */
    public function getIdNumber(): ?string
    {
        return $this->id_number;
    }

    /**
     * @param string|null $id_number
     */
    public function setIdNumber(?string $id_number): void
    {
        $this->id_number = $id_number;
    }

    /**
     * @return string|null
     */
    public function getPhonePrefix(): ?string
    {
        return $this->phone_prefix;
    }

    /**
     * @param string|null $phone_prefix
     */
    public function setPhonePrefix(?string $phone_prefix): void
    {
        $this->phone_prefix = $phone_prefix;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    /**
     * @param string|null $phone_number
     */
    public function setPhoneNumber(?string $phone_number): void
    {
        $this->phone_number = $phone_number;
    }

    /**
     * @return string|null
     */
    public function getContactName(): ?string
    {
        return $this->contact_name;
    }

    /**
     * @param string|null $contact_name
     */
    public function setContactName(?string $contact_name): void
    {
        $this->contact_name = $contact_name;
    }

    /**
     * @return string|null
     */
    public function getContactFamilyName(): ?string
    {
        return $this->contact_family_name;
    }

    /**
     * @param string|null $contact_family_name
     */
    public function setContactFamilyName(?string $contact_family_name): void
    {
        $this->contact_family_name = $contact_family_name;
    }
}
