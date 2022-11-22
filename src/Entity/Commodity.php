<?php

namespace Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Component\Assistance\Enum\CommodityDivision;
use Enum\EnumValueNoFoundException;
use Component\Assistance\DTO\DivisionSummary;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use Entity\Helper\EnumTrait;
use Entity\Helper\StandardizedPrimaryKey;

/**
 * Commodity
 *
 * @ORM\Table(name="commodity")
 * @ORM\Entity(repositoryClass="Repository\CommodityRepository")
 */
class Commodity
{
    use StandardizedPrimaryKey;
    use EnumTrait;

    /**
     * @var string|null
     * @SymfonyGroups({"FullAssistance", "SmallAssistance"})
     * @ORM\Column(name="modality_type", type="enum_modality_type", nullable=true)
     */
    private $modalityType;

    /**
     * @var string
     * @SymfonyGroups({"FullAssistance", "SmallAssistance", "AssistanceOverview"})
     * @ORM\Column(name="unit", type="string", length=45)
     */
    private $unit;

    /**
     * @var float
     * @SymfonyGroups({"FullAssistance", "SmallAssistance", "AssistanceOverview"})
     * @ORM\Column(name="value", type="float")
     */
    private $value;

    /**
     * @var Assistance
     * @ORM\ManyToOne(targetEntity="Entity\Assistance", inversedBy="commodities")
     * @ORM\JoinColumn(name="assistance_id")
     */
    private $assistance;

    /**
     * @var string
     * @SymfonyGroups({"FullAssistance", "SmallAssistance", "AssistanceOverview"})
     * @ORM\Column(name="description", type="string",length=511, nullable=true)
     */
    private $description;

    /**
     * @var string|null
     *
     * @ORM\Column(name="division", type="enum_assitance_commodity_division", nullable=true)
     */
    private $division;

    /**
     * @var DivisionGroup[]|Collection
     * @ORM\OneToMany(targetEntity="Entity\DivisionGroup", mappedBy="commodity", cascade={"persist", "remove"})
     */
    private $divisionGroups;

    /**
     * Set unit.
     *
     * @param string $unit
     *
     * @return Commodity
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * Get unit.
     *
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Set value.
     *
     * @param float $value
     *
     * @return Commodity
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value.
     *
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set assistance.
     *
     * @param Assistance|null $assistance
     *
     * @return Commodity
     */
    public function setAssistance(Assistance $assistance = null)
    {
        $this->assistance = $assistance;

        return $this;
    }

    /**
     * Get assistance.
     *
     * @return Assistance|null
     */
    public function getAssistance()
    {
        return $this->assistance;
    }

    /**
     * Set modalityType.
     *
     * @param string|null $modalityType
     *
     * @return Commodity
     */
    public function setModalityType(?string $modalityType = null)
    {
        $this->modalityType = $modalityType;

        return $this;
    }

    /**
     * Get modalityType.
     *
     * @return string|null
     */
    public function getModalityType(): ?string
    {
        return $this->modalityType;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Commodity
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getDivision(): ?string
    {
        return $this->division;
    }

    /**
     * @param string|null $division
     *
     * @throws EnumValueNoFoundException
     */
    public function setDivision(?string $division): void
    {
        self::validateValue('division', CommodityDivision::class, $division, true);

        $this->division = $division ? CommodityDivision::valueFromAPI($division) : null;
    }

    /**
     * @return DivisionGroup[]|Collection
     */
    public function getDivisionGroups()
    {
        return $this->divisionGroups;
    }

    /**
     * @param DivisionGroup $divisionGroup
     *
     * @return void
     */
    public function addDivisionGroup(DivisionGroup $divisionGroup): void
    {
        $divisionGroup->setCommodity($this);
        $this->divisionGroups[] = $divisionGroup;
    }

    /**
     * @return DivisionSummary
     */
    public function getDivisionSummary(): DivisionSummary
    {
        return new DivisionSummary($this->division, $this->divisionGroups);
    }
}
