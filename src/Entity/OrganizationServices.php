<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * OrganizationServices
 *
 * @ORM\Table(name="organization_service")
 * @ORM\Entity(repositoryClass="Repository\OrganizationServicesRepository")
 */
class OrganizationServices implements JsonSerializable
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
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled;

    /**
     * @var json
     *
     * @ORM\Column(name="parameters_value", type="json")
     */
    private $parametersValue;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Entity\Organization", inversedBy="organizationServices")
     */
    private $organization;

    /**
     * @var Service
     *
     * @ORM\ManyToOne(targetEntity="Entity\Service", inversedBy="organizationServices")
     */
    private $service;

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
     * Set enabled.
     *
     * @param bool $enabled
     *
     * @return OrganizationServices
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled.
     *
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set parametersValue.
     *
     * @param json $parametersValue
     *
     * @return OrganizationServices
     */
    public function setParametersValue($parametersValue)
    {
        $this->parametersValue = $parametersValue;

        return $this;
    }

    /**
     * Get parametersValue.
     *
     * @return json
     */
    public function getParametersValue()
    {
        return $this->parametersValue;
    }

    /**
     * Get the value of a specific parameter.
     *
     * @param string $parameterName
     *
     * @return string
     */
    public function getParameterValue(string $parameterName)
    {
        return $this->parametersValue[$parameterName];
    }

    /**
     * Set organization.
     *
     * @param Organization $organization
     *
     * @return OrganizationServices
     */
    public function setOrganization(Organization $organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get Organization.
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set Service.
     *
     * @param Service $service
     *
     * @return OrganizationServices
     */
    public function setService(Service $service = null)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Get Service.
     *
     * @return Service
     */
    public function getService()
    {
        return $this->service;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'enabled' => $this->enabled,
            'service' => $this->service,
        ];
    }
}
