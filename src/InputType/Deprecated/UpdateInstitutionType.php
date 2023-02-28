<?php

declare(strict_types=1);

namespace InputType\Deprecated;

use Entity\Institution;
use InputType\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UpdateInstitutionType implements InputTypeInterface
{
    /**
     * @var string
     */
    #[Assert\Length(max: 255)]
    protected $name;

    /**
     * @var string|null
     */
    #[Assert\Length(max: 255)]
    #[Assert\Choice(choices: Institution::TYPE_ALL)]
    protected $type;

    #[Assert\Valid]
    private ?\InputType\Deprecated\BeneficiaryAddressType $address = null;

    #[Assert\Length(max: 255)]
    private ?string $latitude = null;

    #[Assert\Length(max: 255)]
    private ?string $longitude = null;

    #[Assert\Valid]
    private ?\InputType\Deprecated\NationalIdType $national_id = null;

    #[Assert\Length(max: 255)]
    #[Assert\Expression('this.getPhoneNumber() == null or value != null')]
    private ?string $phone_prefix = null;

    #[Assert\Length(max: 255)]
    #[Assert\Expression('this.getPhoneNumber() == null or value != null')]
    private ?string $phone_type = null;

    #[Assert\Length(max: 255)]
    private ?string $phone_number = null;

    #[Assert\Length(max: 255)]
    private ?string $contact_name = null;

    #[Assert\Length(max: 255)]
    private ?string $contact_family_name = null;

    /**
     * @var int[]
     */
    #[Assert\NotNull]
    #[Assert\Count(min: 1)]
    public $projects;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getAddress(): ?BeneficiaryAddressType
    {
        return $this->address;
    }

    public function setAddress(?BeneficiaryAddressType $address): void
    {
        $this->address = $address;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function getNationalId(): ?NationalIdType
    {
        return $this->national_id;
    }

    public function setNationalId(?NationalIdType $national_id): void
    {
        $this->national_id = $national_id;
    }

    public function getPhonePrefix(): ?string
    {
        return $this->phone_prefix;
    }

    public function setPhonePrefix(?string $phone_prefix): void
    {
        $this->phone_prefix = $phone_prefix;
    }

    public function getPhoneType(): ?string
    {
        return $this->phone_type;
    }

    public function setPhoneType(?string $phone_type): void
    {
        $this->phone_type = $phone_type;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(?string $phone_number): void
    {
        $this->phone_number = $phone_number;
    }

    public function getContactName(): ?string
    {
        return $this->contact_name;
    }

    public function setContactName(?string $contact_name): void
    {
        $this->contact_name = $contact_name;
    }

    public function getContactFamilyName(): ?string
    {
        return $this->contact_family_name;
    }

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
     * @param int[]|null $projects
     */
    public function setProjects(?array $projects): void
    {
        $this->projects = $projects;
    }
}
