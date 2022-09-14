<?php declare(strict_types=1);

namespace NewApiBundle\Entity;

use DistributionBundle\Entity\Commodity;
use Doctrine\ORM\Mapping as ORM;
/**
 * @ORM\Table(name="division_group")
 * @ORM\Entity(repositoryClass="NewApiBundle\Repository\Assistance\DivisionGroupRepository")
 */
class DivisionGroup extends AbstractEntity
{
    /**
     * @var Commodity
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\Commodity")
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
