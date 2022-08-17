<?php
namespace InputType\Deprecated;

use InputType\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateCommunityType implements InputTypeInterface
{
    /**
     * @var BeneficiaryAddressType|null
     * @Assert\Valid()
     */
    public $address;
    /**
     * @var string|null
     * @Assert\Length(max="255")
     */
    public $latitude;
    /**
     * @var string|null
     * @Assert\Length(max="255")
     */
    public $longitude;
    /**
     * @var NationalIdType|null
     * @Assert\Valid()
     */
    public $national_id;
    /**
     * @var string|null
     * @Assert\Length(max="255")
     * @Assert\Expression("this.getPhoneNumber() == null or value != null")
     */
    public $phone_prefix;
    /**
     * @var string|null
     * @Assert\Length(max="255")
     * @Assert\Expression("this.getPhoneNumber() == null or value != null")
     */
    private $phone_type;
    /**
     * @var string|null
     * @Assert\Length(max="255")
     */
    public $phone_number;
    /**
     * @var string|null
     * @Assert\Length(max="255")
     */
    public $contact_name;
    /**
     * @var string|null
     * @Assert\Length(max="255")
     */
    public $contact_family_name;
    /**
     * @var int[]
     * @Assert\NotNull
     * @Assert\Count(min="1")
     */
    public $projects;

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
     * @return NationalIdType|null
     */
    public function getNationalId(): ?NationalIdType
    {
        return $this->national_id;
    }

    /**
     * @param NationalIdType|null $national_id
     */
    public function setNational_id(?NationalIdType $national_id): void
    {
        $this->national_id = $national_id;
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
    public function getPhoneType(): ?string
    {
        return $this->phone_type;
    }

    /**
     * @param string|null $phone_type
     */
    public function setPhoneType(?string $phone_type): void
    {
        $this->phone_type = $phone_type;
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

    /**
     * @return int[]|null
     */
    public function getProjects(): ?array
    {
        return $this->projects;
    }

    /**
     * @param int[] $projects
     */
    public function setProjects(array $projects): void
    {
        $this->projects = $projects;
    }

}
