<?php

namespace CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AbstractEntity;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * OrganizationServices
 *
 * @ORM\Table(name="organization_service")
 * @ORM\Entity(repositoryClass="CommonBundle\Repository\OrganizationServicesRepository")
 */
class OrganizationServices extends AbstractEntity implements \JsonSerializable
{
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
     * @ORM\ManyToOne(targetEntity="CommonBundle\Entity\Organization", inversedBy="organizationServices")
     */
    private $organization;

    /**
     * @var Service
     *
     * @ORM\ManyToOne(targetEntity="CommonBundle\Entity\Service", inversedBy="organizationServices")
     */
    private $service;


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
     * @param \CommonBundle\Entity\Organization $organization
     *
     * @return OrganizationServices
     */
    public function setOrganization(\CommonBundle\Entity\Organization $organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get Organization.
     *
     * @return \CommonBundle\Entity\Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set Service.
     *
     * @param \CommonBundle\Entity\Service $service
     *
     * @return OrganizationServices
     */
    public function setService(\CommonBundle\Entity\Service $service = null)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Get Service.
     *
     * @return \CommonBundle\Entity\Service
     */
    public function getService()
    {
        return $this->service;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'enabled' => $this->enabled,
            'service' => $this->service,
        ];
    }
}
