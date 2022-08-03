<?php

declare(strict_types=1);

namespace NewApiBundle\InputType\Assistance;

use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;
use NewApiBundle\Validator\Constraints\Enum;

class CommodityInputType implements InputTypeInterface
{
    /**
     * @Assert\Type("string")
     * @Assert\NotBlank
     * @Assert\NotNull
     * @Enum(enumClass="NewApiBundle\Enum\ModalityType")
     */
    private $modalityType;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="45")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $unit;

    /**
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $value;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="511")
     */
    private $description;

    /**
     * @Assert\Type("string")
     * @Enum(enumClass="NewApiBundle\Component\Assistance\Enum\CommodityDivision")
     */
    private $division;

    /**
     * @return string
     */
    public function getModalityType()
    {
        return $this->modalityType;
    }

    /**
     * @param string $modalityType
     */
    public function setModalityType($modalityType)
    {
        $this->modalityType = $modalityType;
    }

    /**
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param string $unit
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return floatval($this->value);
    }

    /**
     * @param float $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getDivision()
    {
        return $this->division;
    }

    /**
     * @param string|null $division
     */
    public function setDivision($division)
    {
        $this->division = $division;
    }
}
