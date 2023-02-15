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
 */
#[ORM\Table(name: 'commodity')]
#[ORM\Entity(repositoryClass: 'Repository\CommodityRepository')]
class Commodity
{
    use StandardizedPrimaryKey;
    use EnumTrait;

    #[SymfonyGroups(['FullAssistance', 'SmallAssistance'])]
    #[ORM\Column(name: 'modality_type', type: 'enum_modality_type', nullable: true)]
    private string|null $modalityType = null;

    #[SymfonyGroups(['FullAssistance', 'SmallAssistance', 'AssistanceOverview'])]
    #[ORM\Column(name: 'unit', type: 'string', length: 45)]
    private string $unit;

    #[SymfonyGroups(['FullAssistance', 'SmallAssistance', 'AssistanceOverview'])]
    #[ORM\Column(name: 'value', type: 'float')]
    private float $value;

    #[ORM\ManyToOne(targetEntity: 'Entity\Assistance', inversedBy: 'commodities')]
    #[ORM\JoinColumn(name: 'assistance_id')]
    private ?\Entity\Assistance $assistance = null;

    #[SymfonyGroups(['FullAssistance', 'SmallAssistance', 'AssistanceOverview'])]
    #[ORM\Column(name: 'description', type: 'string', length: 511, nullable: true)]
    private string|null $description = null;

    #[ORM\Column(name: 'division', type: 'enum_assitance_commodity_division', nullable: true)]
    private string|null $division = null;

    /**
     * @var DivisionGroup[]|Collection
     */
    #[ORM\OneToMany(mappedBy: 'commodity', targetEntity: 'Entity\DivisionGroup', cascade: ['persist', 'remove'], fetch: 'EAGER')]
    private Collection |array|null $divisionGroups = null;

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

    public function getDivision(): ?string
    {
        return $this->division;
    }

    /**
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
    public function getDivisionGroups(): array| Collection
    {
        return $this->divisionGroups;
    }

    public function addDivisionGroup(DivisionGroup $divisionGroup): void
    {
        $divisionGroup->setCommodity($this);
        $this->divisionGroups[] = $divisionGroup;
    }

    public function getDivisionSummary(): DivisionSummary
    {
        return new DivisionSummary($this->division, $this->divisionGroups);
    }
}
