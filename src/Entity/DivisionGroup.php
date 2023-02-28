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
     * @ORM\ManyToOne(targetEntity="Entity\Commodity")
     * @ORM\JoinColumn(name="commodity_id", nullable=false)
     */
    private ?\Entity\Commodity $commodity = null;

    /**
     * @ORM\Column(name="range_from", type="integer", nullable=false)
     */
    private ?int $rangeFrom = null;

    /**
     * @ORM\Column(name="range_to", type="integer", nullable=true)
     */
    private ?int $rangeTo = null;

    /**
     * @ORM\Column(name="value", type="decimal")
     */
    private ?string $value = null;

    public function getCommodity(): Commodity
    {
        return $this->commodity;
    }

    public function setCommodity(Commodity $commodity): void
    {
        $this->commodity = $commodity;
    }

    public function getRangeFrom(): int
    {
        return $this->rangeFrom;
    }

    public function setRangeFrom(int $rangeFrom): void
    {
        $this->rangeFrom = $rangeFrom;
    }

    public function getRangeTo(): ?int
    {
        return $this->rangeTo;
    }

    public function setRangeTo(?int $rangeTo): void
    {
        $this->rangeTo = $rangeTo;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
