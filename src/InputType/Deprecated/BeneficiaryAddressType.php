<?php

namespace InputType\Deprecated;

use InputType\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class BeneficiaryAddressType implements InputTypeInterface
{
    #[Assert\Length(max: 255)]
    private ?string $street = null;

    #[Assert\Length(max: 255)]
    private ?string $number = null;

    #[Assert\Length(max: 255)]
    private ?string $postcode = null;

    #[Assert\Valid]
    private ?\InputType\Deprecated\LocationType $location = null;

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): void
    {
        $this->street = $street;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): void
    {
        $this->number = $number;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function setPostcode(?string $postcode): void
    {
        $this->postcode = $postcode;
    }

    public function getLocation(): ?LocationType
    {
        return $this->location;
    }

    public function setLocation(?LocationType $location): void
    {
        $this->location = $location;
    }
}
