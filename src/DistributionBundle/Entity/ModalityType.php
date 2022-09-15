<?php

namespace DistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AbstractEntity;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * @ORM\Table(name="modality_type")
 * @ORM\Entity(repositoryClass="DistributionBundle\Repository\ModalityTypeRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ModalityType extends AbstractEntity
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @SymfonyGroups({"FullModalityType", "FullAssistance", "SmallAssistance"})
     */
    private $name;

    /**
     * @var Modality
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\Modality", inversedBy="modalityTypes")
     * @SymfonyGroups({"FullModalityType", "FullAssistance", "SmallAssistance"})
     */
    private $modality;

    /**
     * Internal modality types shold not be presented to FE.
     *
     * @var bool
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $internal = false;


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
     * @param Modality|null $modality
     *
     * @return ModalityType
     */
    public function setModality(Modality $modality = null)
    {
        $this->modality = $modality;

        return $this;
    }

    /**
     * Get modality.
     *
     * @return Modality|null
     */
    public function getModality()
    {
        return $this->modality;
    }

    public function isInternal(): bool
    {
        return $this->internal;
    }

    public function isGeneralRelief(): bool
    {
        $grModalities = ['In Kind', 'Other'];
        $grTypes = ['Paper Voucher', 'Cash'];

        return in_array($this->getModality()->getName(), $grModalities) || in_array($this->getName(), $grTypes);
    }
}
