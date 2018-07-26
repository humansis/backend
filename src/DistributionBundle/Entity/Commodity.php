<?php

namespace DistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

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
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\ModalityType")
     */
    private $modalityType;

    /**
     * @var string
     *
     * @ORM\Column(name="unit", type="string", length=45)
     */
    private $unit;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="float")
     */
    private $value;

    /**
     * @var DistributionData
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\DistributionData", inversedBy="commodities")
     */
    private $distributionData;

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
}
