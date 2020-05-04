<?php
namespace BeneficiaryBundle\InputType;

use CommonBundle\InputType\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class BeneficiaryAddressType implements InputTypeInterface
{
    /**
     * @var string
     * @Assert\Length(max="255")
     */
    private $street;
    /**
     * @var string
     * @Assert\Length(max="255")
     */
    private $number;
    /**
     * @var string
     * @Assert\Length(max="255")
     */
    private $postcode;
    /**
     * @var LocationType|null
     * @Assert\Valid()
     */
    private $location;

    /**
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * @param string $street
     */
    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @param string $number
     */
    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    /**
     * @return string
     */
    public function getPostcode(): string
    {
        return $this->postcode;
    }

    /**
     * @param string $postcode
     */
    public function setPostcode(string $postcode): void
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
