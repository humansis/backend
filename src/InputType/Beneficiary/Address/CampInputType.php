<?php

declare(strict_types=1);

namespace InputType\Beneficiary\Address;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CampInputType implements InputTypeInterface
{
    /**
     * @Assert\Type(type={"string", "numeric"})
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
