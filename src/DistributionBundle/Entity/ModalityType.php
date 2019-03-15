<?php

namespace DistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * ModalityType
 *
 * @ORM\Table(name="modality_type")
 * @ORM\Entity(repositoryClass="DistributionBundle\Repository\ModalityTypeRepository")
 */
class ModalityType
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"FullModalityType"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Groups({"FullModalityType", "FullDistribution"})
     */
    private $name;

    /**
     * @var Modality
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\Modality", inversedBy="modalityTypes")
     * @Groups({"FullModalityType", "FullDistribution"})
     */
    private $modality;


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
     * @return ModalityType
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
     * Set modality.
     *
     * @param \DistributionBundle\Entity\Modality|null $modality
     *
     * @return ModalityType
     */
    public function setModality(\DistributionBundle\Entity\Modality $modality = null)
    {
        $this->modality = $modality;

        return $this;
    }

    /**
     * Get modality.
     *
     * @return \DistributionBundle\Entity\Modality|null
     */
    public function getModality()
    {
        return $this->modality;
    }
}
