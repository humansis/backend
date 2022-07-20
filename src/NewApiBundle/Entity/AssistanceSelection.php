<?php
declare(strict_types=1);

namespace NewApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Assistance\SelectionCriteria;

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
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\Assistance\SelectionCriteria", mappedBy="assistanceSelection", cascade={"persist"})
     */
    private $selectionCriteria;

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
}
