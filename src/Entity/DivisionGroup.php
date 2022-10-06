<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;

/**
 * @ORM\Table(name="division_group")
 * @ORM\Entity(repositoryClass="Repository\Assistance\DivisionGroupRepository")
 */
class DivisionGroup
{
    use StandardizedPrimaryKey;

    /**
     * @var Commodity
     * @ORM\ManyToOne(targetEntity="Entity\Commodity")
     * @ORM\JoinColumn(name="commodity_id", nullable=false)
     */
    private $commodity;

    /**
     * @var int
     * @ORM\Column(name="range_from", type="integer", nullable=false)
     */
    private $rangeFrom;

    /**
     * @var int|null
     * @ORM\Column(name="range_to", type="integer", nullable=true)
     */
    private $rangeTo;

    /**
     * @var string
     * @ORM\Column(name="value", type="decimal")
     */
    private $value;

    /**
     * @return Commodity
     */
    public function getCommodity(): Commodity
    {
        return $this->commodity;
    }

    /**
     * @param Commodity $commodity
     */
    public function setCommodity(Commodity $commodity): void
    {
        $this->commodity = $commodity;
    }

    /**
     * @return int
     */
    public function getRangeFrom(): int
    {
        return $this->rangeFrom;
    }

    /**
     * @param int $rangeFrom
     */
    public function setRangeFrom(int $rangeFrom): void
    {
        $this->rangeFrom = $rangeFrom;
    }

    /**
     * @return int|null
     */
    public function getRangeTo(): ?int
    {
        return $this->rangeTo;
    }

    /**
     * @param int|null $rangeTo
     */
    public function setRangeTo(?int $rangeTo): void
    {
        $this->rangeTo = $rangeTo;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
