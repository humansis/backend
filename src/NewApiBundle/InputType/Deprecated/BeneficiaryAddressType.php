<?php
namespace NewApiBundle\InputType\Deprecated;

use CommonBundle\InputType\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class BeneficiaryAddressType implements InputTypeInterface
{
    /**
     * @var string|null
     * @Assert\Length(max="255")
     */
    private $street;
    /**
     * @var string|null
     * @Assert\Length(max="255")
     */
    private $number;
    /**
     * @var string|null
     * @Assert\Length(max="255")
     */
    private $postcode;
    /**
     * @var LocationType|null
     * @Assert\Valid()
     */
    private $location;

    /**
     * @return string|null
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * @param string|null $street
     */
    public function setStreet(?string $street): void
    {
        $this->street = $street;
    }

    /**
     * @return string|null
     */
    public function getNumber(): ?string
    {
        return $this->number;
    }

    /**
     * @param string|null $number
     */
    public function setNumber(?string $number): void
    {
        $this->number = $number;
    }

    /**
     * @return string|null
     */
    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    /**
     * @param string|null $postcode
     */
    public function setPostcode(?string $postcode): void
    {
        $this->postcode = $postcode;
    }

    /**
     * @return LocationType|null
     */
    public function getLocation(): ?LocationType
    {
        return $this->location;
    }

    /**
     * @param LocationType|null $location
     */
    public function setLocation(?LocationType $location): void
    {
        $this->location = $location;
    }
}
