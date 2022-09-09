<?php

namespace DistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Modality
 *
 * @ORM\Table(name="modality")
 * @ORM\Entity(repositoryClass="DistributionBundle\Repository\ModalityRepository")
 */
class Modality
{
    use StandardizedPrimaryKey;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     * @SymfonyGroups({"FullModality", "FullModalityType", "FullAssistance", "SmallAssistance"})
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="DistributionBundle\Entity\ModalityType", mappedBy="modality")
     */
    private $modalityTypes;


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
