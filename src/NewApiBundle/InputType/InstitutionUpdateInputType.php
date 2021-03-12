<?php

namespace NewApiBundle\InputType;

use NewApiBundle\InputType\Beneficiary\AddressInputType;
use NewApiBundle\Request\InputTypeInterface;
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
     * @var ContactInputType
     * @Assert\Valid
     * @Assert\NotNull
     */
    private $contact;

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
     * @return ContactInputType
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @param ContactInputType|null $contact
     */
    public function setContact(?ContactInputType $contact): void
    {
        $this->contact = $contact;
    }
}
