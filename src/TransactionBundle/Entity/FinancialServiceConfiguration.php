<?php

namespace TransactionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FinancialServiceConfiguration
 *
 * @ORM\Table(name="financial_service_configuration")
 * @ORM\Entity(repositoryClass="TransactionBundle\Repository\FinancialServiceConfigurationRepository")
 */
class FinancialServiceConfiguration
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
     * @ORM\Column(name="service", type="string", length=45)
     */
    private $service;

    /**
     * @var Account
     *
     * @ORM\ManyToOne(targetEntity="TransactionBundle\Entity\Account")
     */
    private $account;

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
     * Set service.
     *
     * @param string $service
     *
     * @return FinancialServiceConfiguration
     */
    public function setService($service)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Get service.
     *
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Set account.
     *
     * @param \TransactionBundle\Entity\Account|null $account
     *
     * @return FinancialServiceConfiguration
     */
    public function setAccount(\TransactionBundle\Entity\Account $account = null)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get account.
     *
     * @return \TransactionBundle\Entity\Account|null
     */
    public function getAccount()
    {
        return $this->account;
    }
}
