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
     * @var string
     *
     * @ORM\Column(name="modality", type="string", length=45)
     */
    private $modality;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=45)
     */
    private $type;

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
     * @var string
     *
     * @ORM\Column(name="conditions", type="string", length=45)
     */
    private $conditions;

    /**
     * @var DistributionData
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\DistributionData")
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
     * Set modality.
     *
     * @param string $modality
     *
     * @return Commodity
     */
    public function setModality($modality)
    {
        $this->modality = $modality;

        return $this;
    }

    /**
     * Get modality.
     *
     * @return string
     */
    public function getModality()
    {
        return $this->modality;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return Commodity
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
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
     * Set conditions.
     *
     * @param string $conditions
     *
     * @return Commodity
     */
    public function setConditions($conditions)
    {
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * Get conditions.
     *
     * @return string
     */
    public function getConditions()
    {
        return $this->conditions;
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
}
