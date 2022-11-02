<?php

declare(strict_types=1);

namespace InputType\Beneficiary\Address;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class TemporarySettlementAddressInputType implements InputTypeInterface
{
    #[Assert\Type('scalar')]
    #[Assert\Length(max: 45)]
    #[Assert\NotBlank(allowNull: true)]
    private $number;

    #[Assert\Type('scalar')]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank(allowNull: true)]
    private $street;

    #[Assert\Type('scalar')]
    #[Assert\Length(max: 45)]
    #[Assert\NotBlank(allowNull: true)]
    private $postcode;

    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $locationId;

    /**
     * @return string|null
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param string|null $number
     */
    public function setNumber($number): void
    {
        $this->number = $number;
    }

    /**
     * @return string|null
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param string|null $street
     */
    public function setStreet($street): void
    {
        $this->street = $street;
    }

    /**
     * @return string|null
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * @param string|null $postcode
     */
    public function setPostcode($postcode): void
    {
        $this->postcode = $postcode;
    }

    /**
     * @return int
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * @param int $locationId
     */
    public function setLocationId($locationId): void
    {
        $this->locationId = $locationId;
    }
}
