<?php

namespace DistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Commodity
 *
 * @ORM\Table(name="commodity")
 * @ORM\Entity(repositoryClass="DistributionBundle\Repository\CommodityRepository")
 */
class Commodity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var ModalityType
     * @SymfonyGroups({"FullDistribution", "SmallDistribution"})
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\ModalityType")
     */
    private $modalityType;

    /**
     * @var string
     * @SymfonyGroups({"FullDistribution", "SmallDistribution", "DistributionOverview"})
     * @ORM\Column(name="unit", type="string", length=45)
     */
    private $unit;

    /**
     * @var float
     * @SymfonyGroups({"FullDistribution", "SmallDistribution", "DistributionOverview"})
     * @ORM\Column(name="value", type="float")
     */
    private $value;

    /**
     * @var DistributionData
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\DistributionData", inversedBy="commodities")
     */
    private $distributionData;

    /**
     * @var string
     * @SymfonyGroups({"FullDistribution", "SmallDistribution", "DistributionOverview"})
     * @ORM\Column(name="description", type="string",length=511, nullable=true)
     */
    private $description;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

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
     * Set distributionData.
     *
     * @param \DistributionBundle\Entity\DistributionData|null $distributionData
     *
     * @return Commodity
     */
    public function setDistributionData(\DistributionBundle\Entity\DistributionData $distributionData = null)
    {
        $this->distributionData = $distributionData;

        return $this;
    }

    /**
     * Get distributionData.
     *
     * @return \DistributionBundle\Entity\DistributionData|null
     */
    public function getDistributionData()
    {
        return $this->distributionData;
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
}
