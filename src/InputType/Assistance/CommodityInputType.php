<?php

declare(strict_types=1);

namespace InputType\Assistance;

use Request\InputTypeNullableDenormalizer;
use Symfony\Component\Validator\Constraints as Assert;

class CommodityInputType implements InputTypeNullableDenormalizer
{
    /**
     * @Assert\Type("string") // todo change to enum
     * @Assert\NotBlank
     * @Assert\NotNull
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
     * @Assert\NotBlank(allowNull=true)
     */
    private $value = null;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="511")
     */
    private $description;

    /**
     * @var DivisionInputType|null
     * @Assert\Valid
     * @Assert\NotBlank(allowNull=true)
     */
    private $division;

    /**
     * @param DivisionInputType|null $divisionInputType
     *
     * @return void
     */
    public function setDivision(?DivisionInputType $divisionInputType)
    {
        $this->division = $divisionInputType;
    }

    /**
     * @return DivisionInputType|null
     */
    public function getDivision(): ?DivisionInputType
    {
        return $this->division;
    }

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

}