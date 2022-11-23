<?php

declare(strict_types=1);

namespace InputType;

use InputType\Beneficiary\AddressInputType;
use InputType\Beneficiary\NationalIdCardInputType;
use InputType\Beneficiary\PhoneInputType;
use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class CommunityUpdateInputType
 *
 * @package InputType
 */
class CommunityUpdateInputType implements InputTypeInterface
{
    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    #[Assert\Type('array')]
    private array $projectIds = [];

    #[Assert\Length(max: 45)]
    #[Assert\Type('string')]
    private ?string $longitude = null;

    #[Assert\Length(max: 45)]
    #[Assert\Type('string')]
    private ?string $latitude = null;

    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $contactGivenName = null;

    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $contactFamilyName = null;

    #[Assert\Valid]
    #[Assert\NotNull]
    private ?\InputType\Beneficiary\AddressInputType $address = null;

    #[Assert\Valid]
    private ?\InputType\Beneficiary\NationalIdCardInputType $nationalIdCard = null;

    #[Assert\Valid]
    private ?\InputType\Beneficiary\PhoneInputType $phone = null;

    /**
     * @return int[]
     */
    public function getProjectIds()
    {
        return (array) $this->projectIds;
    }

    /**
     * @param int[]|null $ids
     */
    public function setProjectIds($ids)
    {
        $this->projectIds = $ids;
    }

    /**
     * @return string|null
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param string|null $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }

    /**
     * @return string|null
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param string|null $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    /**
     * @return string
     */
    public function getContactGivenName()
    {
        return $this->contactGivenName;
    }

    /**
     * @param string $contactGivenName
     */
    public function setContactGivenName($contactGivenName): void
    {
        $this->contactGivenName = $contactGivenName;
    }

    /**
     * @return string
     */
    public function getContactFamilyName()
    {
        return $this->contactFamilyName;
    }

    /**
     * @param string $contactFamilyName
     */
    public function setContactFamilyName($contactFamilyName)
    {
        $this->contactFamilyName = $contactFamilyName;
    }

    /**
     * @return AddressInputType
     */
    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress(AddressInputType $address)
    {
        $this->address = $address;
    }

    /**
     * @return NationalIdCardInputType|null
     */
    public function getNationalIdCard()
    {
        return $this->nationalIdCard;
    }

    public function setNationalIdCard(?NationalIdCardInputType $nationalIdCard)
    {
        $this->nationalIdCard = $nationalIdCard;
    }

    /**
     * @return PhoneInputType|null
     */
    public function getPhone()
    {
        return $this->phone;
    }

    public function setPhone(?PhoneInputType $phone): void
    {
        $this->phone = $phone;
    }
}
