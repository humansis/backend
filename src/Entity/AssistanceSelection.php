<?php
declare(strict_types=1);

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Entity\Assistance\SelectionCriteria;

/**
 * @ORM\Entity()
 */
class AssistanceSelection
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
     * @var integer|null
     *
     * @ORM\Column(name="threshold", type="integer", nullable=true)
     */
    private $threshold;

    /**
     * @var Collection|SelectionCriteria[]
     *
     * @ORM\OneToMany(targetEntity="Entity\Assistance\SelectionCriteria", mappedBy="assistanceSelection", cascade={"persist"})
     */
    private $selectionCriteria;

    /**
     * @var Assistance
     * @ORM\OneToOne(targetEntity="Entity\Assistance", mappedBy="assistanceSelection", cascade={"persist", "remove"})
     */
    private $assistance;

    public function __construct()
    {
        $this->selectionCriteria = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
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
    public function getSelectionCriteria()
    {
        return $this->selectionCriteria;
    }

    /**
     * @return Assistance
     */
    public function getAssistance(): Assistance
    {
        return $this->assistance;
    }

    /**
     * @param Assistance $assistance
     */
    public function setAssistance(Assistance $assistance): void
    {
        $this->assistance = $assistance;
    }


}
