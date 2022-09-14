<?php

namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AbstractEntity;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Camp
 *
 * @ORM\Table(name="camp")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\CampRepository")
 */
class Camp extends AbstractEntity
{

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=45)
     * @SymfonyGroups({"FullHousehold", "FullCamp"})
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="CommonBundle\Entity\Location")
     * @SymfonyGroups({"FullHousehold", "SmallHousehold"})
     */
    private $location;


    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Camp
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
     * Set location.
     *
     * @param \CommonBundle\Entity\Location|null $location
     *
     * @return Camp
     */
    public function setLocation(\CommonBundle\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     *
     * @return \CommonBundle\Entity\Location|null
     */
    public function getLocation()
    {
        return $this->location;
    }

}
