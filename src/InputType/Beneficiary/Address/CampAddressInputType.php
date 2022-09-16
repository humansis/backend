<?php

declare(strict_types=1);

namespace InputType\Beneficiary\Address;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CampAddressInputType implements InputTypeInterface
{
    /**
     * @Assert\Type("scalar")
     * @Assert\Length(max="45")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $tentNumber;

    /**
     * @Assert\Valid
     */
    private $camp;

    /**
     * @Assert\Type("integer")
     * @Assert\GreaterThanOrEqual("0")
     */
    private $campId;

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
     * @return CampInputType|null
     */
    public function getCamp()
    {
        return $this->camp;
    }

    /**
     * @param CampInputType|null $campInputType
     */
    public function setCamp(CampInputType $campInputType)
    {
        $this->camp = $campInputType;
    }

    /**
     * @return int|null
     */
    public function getCampId()
    {
        return $this->campId;
    }

    /**
     * @param int|null $campId
     */
    public function setCampId($campId)
    {
        $this->campId = $campId;
    }
}
