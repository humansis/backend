<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;

/**
 * Camp
 */
#[ORM\Table(name: 'camp')]
#[ORM\Entity(repositoryClass: 'Repository\CampRepository')]
class Camp
{
    use StandardizedPrimaryKey;

    #[ORM\Column(name: 'name', type: 'string', length: 45)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: 'Entity\Location')]
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
     * @param Location|null $location
     *
     * @return Camp
     */
    public function setLocation(Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     *
     * @return Location|null
     */
    public function getLocation()
    {
        return $this->location;
    }
}
