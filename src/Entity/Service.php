<?php

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;
use JsonSerializable;

/**
 * Service
 *
 * @ORM\Table(name="service")
 * @ORM\Entity(repositoryClass="Repository\ServiceRepository")
 */
class Service implements JsonSerializable
{
    use StandardizedPrimaryKey;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var json
     *
     * @ORM\Column(name="parameters", type="json")
     */
    private $parameters;

    /**
     * @var string
     *
     * @ORM\Column(name="country", type="string", length=255, nullable=true)
     */
    private $country;

    /**
     * @var OrganizationServices $organizationServices
     *
     * @ORM\OneToMany(targetEntity="Entity\OrganizationServices", mappedBy="service", cascade={"remove"})
     */
    private $organizationServices;

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Service
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
     * Set parameters.
     *
     * @param json $parameters
     *
     * @return Service
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Get parameters.
     *
     * @return json
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Set country.
     *
     * @param string $country
     *
     * @return Service
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Add OrganizationServices.
     *
     * @param OrganizationServices $organizationServices
     *
     * @return OrganizationServices
     */
    public function addOrganizationServices(OrganizationServices $organizationServices)
    {
        if (null === $this->organizationServices) {
            $this->organizationServices = new ArrayCollection();
        }
        $this->organizationServices[] = $organizationServices;

        return $this;
    }

    /**
     * Remove OrganizationServices.
     *
     * @param OrganizationServices $organizationServices
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeOrganizationServices(OrganizationServices $organizationServices)
    {
        return $this->organizationServices->removeElement($organizationServices);
    }

    /**
     * Get OrganizationServices.
     *
     * @return Collection
     */
    public function getOrganizationServices()
    {
        return $this->organizationServices;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'parameters' => $this->parameters,
            'country' => $this->country,
        ];
    }
}
