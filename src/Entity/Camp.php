<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Camp
 *
 * @ORM\Table(name="camp")
 * @ORM\Entity(repositoryClass="Repository\CampRepository")
 */
class Camp
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=45)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Location")
     */
    private $location;


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
     * @param \Entity\Location|null $location
     *
     * @return Camp
     */
    public function setLocation(\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     *
     * @return \Entity\Location|null
     */
    public function getLocation()
    {
        return $this->location;
    }

}
