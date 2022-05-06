<?php

namespace DistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Component\Assistance\Enum\CommodityDivision;
use NewApiBundle\DBAL\AssistanceCommodityDivisionEnum;
use NewApiBundle\Enum\PersonGender;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use NewApiBundle\Entity\Helper\EnumTrait;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;

/**
 * Commodity
 *
 * @ORM\Table(name="commodity")
 * @ORM\Entity(repositoryClass="DistributionBundle\Repository\CommodityRepository")
 */
class Commodity
{
    use StandardizedPrimaryKey;
    use EnumTrait;

    /**
     * @var ModalityType
     * @SymfonyGroups({"FullAssistance", "SmallAssistance"})
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\ModalityType")
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
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\Assistance", inversedBy="commodities")
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
     * @param \DistributionBundle\Entity\Assistance|null $assistance
     *
     * @return Commodity
     */
    public function setAssistance(\DistributionBundle\Entity\Assistance $assistance = null)
    {
        $this->assistance = $assistance;

        return $this;
    }

    /**
     * Get assistance.
     *
     * @return \DistributionBundle\Entity\Assistance|null
     */
    public function getAssistance()
    {
        return $this->assistance;
    }

    /**
     * Set modalityType.
     *
     * @param \DistributionBundle\Entity\ModalityType|null $modalityType
     *
     * @return Commodity
     */
    public function setModalityType(\DistributionBundle\Entity\ModalityType $modalityType = null)
    {
        $this->modalityType = $modalityType;

        return $this;
    }

    /**
     * Get modalityType.
     *
     * @return \DistributionBundle\Entity\ModalityType|null
     */
    public function getModalityType()
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
     */
    public function setDivision(?string $division): void
    {
        self::validateValue('division', CommodityDivision::class, $division, true);

        $this->division = $division ? CommodityDivision::valueFromAPI($division) : null;
    }
}
