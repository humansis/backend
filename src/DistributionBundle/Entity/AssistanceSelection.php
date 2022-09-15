<?php
declare(strict_types=1);

namespace DistributionBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AbstractEntity;
use NewApiBundle\Entity\Assistance\SelectionCriteria;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class AssistanceSelection extends AbstractEntity
{
    /**
     * @var integer|null
     *
     * @ORM\Column(name="threshold", type="integer", nullable=true)
     */
    private $threshold;

    /**
     * @var Collection|SelectionCriteria[]
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\Assistance\SelectionCriteria", mappedBy="assistanceSelection", cascade={"persist"})
     */
    private $selectionCriteria;

    /**
     * @var Assistance
     * @ORM\OneToOne(targetEntity="DistributionBundle\Entity\Assistance", mappedBy="assistanceSelection")
     */
    private $assistance;

    public function __construct()
    {
        $this->selectionCriteria = new ArrayCollection();
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
