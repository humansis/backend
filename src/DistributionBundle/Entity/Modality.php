<?php

namespace DistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * Modality
 *
 * @ORM\Table(name="modality")
 * @ORM\Entity(repositoryClass="DistributionBundle\Repository\ModalityRepository")
 */
class Modality
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     *
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="DistributionBundle\Entity\ModalityType", mappedBy="modality")
     */
    private $modalityTypes;


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
     * Set name.
     *
     * @param string $name
     *
     * @return Modality
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->modalityTypes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add modalityType.
     *
     * @param \DistributionBundle\Entity\ModalityType $modalityType
     *
     * @return Modality
     */
    public function addModalityType(\DistributionBundle\Entity\ModalityType $modalityType)
    {
        $this->modalityTypes[] = $modalityType;

        return $this;
    }

    /**
     * Remove modalityType.
     *
     * @param \DistributionBundle\Entity\ModalityType $modalityType
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeModalityType(\DistributionBundle\Entity\ModalityType $modalityType)
    {
        return $this->modalityTypes->removeElement($modalityType);
    }

    /**
     * Get modalityTypes.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getModalityTypes()
    {
        return $this->modalityTypes;
    }
}
