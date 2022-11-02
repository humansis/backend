<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;
use JsonSerializable;

/**
 * OrganizationServices
 *
 * @ORM\Table(name="organization_service")
 * @ORM\Entity(repositoryClass="Repository\OrganizationServicesRepository")
 */
class OrganizationServices implements JsonSerializable
{
    use StandardizedPrimaryKey;

    /**
     * @ORM\Column(name="enabled", type="boolean")
     */
    private bool $enabled;

    /**
     * @var json
     *
     * @ORM\Column(name="parameters_value", type="json")
     */
    private $parametersValue;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Organization", inversedBy="organizationServices")
     */
    private ?\Entity\Organization $organization = null;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Service", inversedBy="organizationServices")
     */
    private ?\Entity\Service $service = null;

    /**
     * Set enabled.
     *
     * @param bool $enabled
     *
     * @return OrganizationServices
     */
    public function setEnabled(bool $enabled): OrganizationServices
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled.
     *
     * @return bool
     */
    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Set parametersValue.
     *
     * @param array $parametersValue
     *
     * @return OrganizationServices
     */
    public function setParametersValue(array $parametersValue): OrganizationServices
    {
        $this->parametersValue = $parametersValue;

        return $this;
    }

    /**
     * Get parametersValue.
     *
     * @return array
     */
    public function getParametersValue(): array
    {
        return $this->parametersValue;
    }

    /**
     * Get the value of a specific parameter.
     *
     *
     * @return string
     */
    public function getParameterValue(string $parameterName): string
    {
        return $this->parametersValue[$parameterName];
    }

    /**
     * Set organization.
     *
     *
     * @return OrganizationServices
     */
    public function setOrganization(Organization $organization): OrganizationServices
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get Organization.
     *
     * @return Organization
     */
    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    /**
     * Set Service.
     *
     *
     * @return OrganizationServices
     */
    public function setService(Service $service = null): OrganizationServices
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Get Service.
     *
     * @return Service
     */
    public function getService(): Service
    {
        return $this->service;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'enabled' => $this->enabled,
            'service' => $this->service,
        ];
    }
}
