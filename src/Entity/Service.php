<?php

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Service
 *
 * @ORM\Table(name="service")
 * @ORM\Entity(repositoryClass="Repository\ServiceRepository")
 */
class Service implements JsonSerializable
{
    /**
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    private string $name;

    /**
     * @var json
     *
     * @ORM\Column(name="parameters", type="json")
     */
    private $parameters;

    /**
     * @ORM\Column(name="country", type="string", length=255, nullable=true)
     */
    private ?string $country;

    /**
     * @var OrganizationServices $organizationServices
     *
     * @ORM\OneToMany(targetEntity="Entity\OrganizationServices", mappedBy="service", cascade={"remove"})
     */
    private $organizationServices;

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

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'parameters' => $this->parameters,
            'country' => $this->country,
        ];
    }
}
