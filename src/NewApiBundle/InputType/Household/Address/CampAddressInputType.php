<?php

declare(strict_types=1);

namespace NewApiBundle\InputType\Household\Address;

use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CampAddressInputType implements InputTypeInterface
{
    /**
     * @Assert\Type("string")
     * @Assert\Length(max="45")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $tentNumber;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="45")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $name;

    /**
     * @Assert\Type("integer")
     * @Assert\GreaterThanOrEqual("0")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $locationId;

    /**
     * @return string|null
     */
    public function getTentNumber()
    {
        return $this->tentNumber;
    }

    /**
     * @param string|null $tentNumber
     */
    public function setTentNumber($tentNumber): void
    {
        $this->tentNumber = $tentNumber;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName($name): void
    {
        $this->name = $name;
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
