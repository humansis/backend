<?php

namespace InputType;

use InputType\Beneficiary\AddressInputType;
use InputType\Beneficiary\NationalIdCardInputType;
use InputType\Beneficiary\PhoneInputType;
use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"InstitutionUpdateInputType", "Strict"})
 */
class InstitutionUpdateInputType implements InputTypeInterface
{
    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    private $projectIds = [];

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="45")
     */
    private $longitude;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="45")
     */
    private $latitude;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     * @Assert\NotNull
     */
    private $name;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    private $contactGivenName;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    private $contactFamilyName;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     * @Assert\NotNull
     */
    private $type;

    /**
     * @var AddressInputType
     *
     * @Assert\Valid
     */
    private $address;

    /**
     * @var NationalIdCardInputType
     *
     * @Assert\Valid
     */
    private $nationalIdCard;

    /**
     * @var PhoneInputType
     *
     * @Assert\Valid
     */
    private $phone;

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getContactGivenName()
    {
        return $this->contactGivenName;
    }

    /**
     * @param string|null $contactGivenName
     */
    public function setContactGivenName($contactGivenName)
    {
        $this->contactGivenName = $contactGivenName;
    }

    /**
     * @return string|null
     */
    public function getContactFamilyName()
    {
        return $this->contactFamilyName;
    }

    /**
     * @param string|null $contactFamilyName
     */
    public function setContactFamilyName($contactFamilyName)
    {
        $this->contactFamilyName = $contactFamilyName;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return AddressInputType
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param AddressInputType $address
     */
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

    /**
     * @param NationalIdCardInputType|null $nationalIdCard
     */
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

    /**
     * @param PhoneInputType|null $phone
     */
    public function setPhone(?PhoneInputType $phone): void
    {
        $this->phone = $phone;
    }

}
