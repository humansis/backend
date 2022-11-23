<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Entity\Assistance\SelectionCriteria;
use Entity\Helper\StandardizedPrimaryKey;

/**
 * @ORM\Entity()
 */
class AssistanceSelection
{
    use StandardizedPrimaryKey;

    /**
     * @ORM\Column(name="threshold", type="integer", nullable=true)
     */
    private ?int $threshold = null;

    /**
     * @var Collection|SelectionCriteria[]
     *
     * @ORM\OneToMany(targetEntity="Entity\Assistance\SelectionCriteria", mappedBy="assistanceSelection", cascade={"persist"})
     */
    private \Doctrine\Common\Collections\Collection|array $selectionCriteria;

    /**
     * @ORM\OneToOne(targetEntity="Entity\Assistance", mappedBy="assistanceSelection", cascade={"persist", "remove"})
     */
    private ?\Entity\Assistance $assistance = null;

    public function __construct()
    {
        $this->selectionCriteria = new ArrayCollection();
    }

    public function getThreshold(): ?int
    {
        return $this->threshold;
    }

    /**
     * @param int $threshold
     */
    public function setThreshold(?int $threshold): void
    {
        $this->threshold = $threshold;
    }

    /**
     * @return Collection|SelectionCriteria[]
     */
    public function getSelectionCriteria(): \Doctrine\Common\Collections\Collection|array
    {
        return $this->selectionCriteria;
    }

    public function getAssistance(): Assistance
    {
        return $this->assistance;
    }

    public function setAssistance(Assistance $assistance): void
    {
        $this->assistance = $assistance;
    }
}
