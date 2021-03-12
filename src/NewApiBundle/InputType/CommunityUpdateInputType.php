<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\InputType\Beneficiary\AddressInputType;
use NewApiBundle\InputType\Beneficiary\NationalIdCardInputType;
use NewApiBundle\InputType\Beneficiary\PhoneInputType;
use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class CommunityUpdateInputType
 * @package NewApiBundle\InputType
 */
class CommunityUpdateInputType implements InputTypeInterface
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
     * @var string|null $longitude
     *
     * @Assert\Length(max="45")
     * @Assert\Type("string")
     */
    private $longitude;

    /**
     * @var string|null $latitude
     *
     * @Assert\Length(max="45")
     * @Assert\Type("string")
     */
    private $latitude;

    /**
     * @var AddressInputType $address
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
