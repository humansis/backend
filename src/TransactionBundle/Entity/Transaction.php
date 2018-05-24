<?php

namespace TransactionBundle\Entity;

use DistributionBundle\Entity\CommodityDistributionBeneficiary;
use Doctrine\ORM\Mapping as ORM;

/**
 * Transaction
 *
 * @ORM\Table(name="transaction")
 * @ORM\Entity(repositoryClass="TransactionBundle\Repository\TransactionRepository")
 */
class Transaction
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
     * @ORM\Column(name="transactionIdServiceProvider", type="string", length=45)
     */
    private $transactionIdServiceProvider;

    /**
     * @var float
     *
     * @ORM\Column(name="amountsent", type="float")
     */
    private $amountsent;

    /**
     * @var bool
     *
     * @ORM\Column(name="statusServiceprovider", type="boolean")
     */
    private $statusServiceprovider;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="transactiontime", type="datetime")
     */
    private $transactiontime;

    /**
     * @var float
     *
     * @ORM\Column(name="moneyReceived", type="float")
     */
    private $moneyReceived;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="pickupdate", type="datetime")
     */
    private $pickupdate;

    /**
     * @var FinancialServiceConfiguration
     *
     * @ORM\ManyToOne(targetEntity="TransactionBundle\Entity\FinancialServiceConfiguration")
     */
    private $financialServiceConfiguration;

    /**
     * @var CommodityDistributionBeneficiary
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\CommodityDistributionBeneficiary")
     */
    private $commodityDistributionBeneficiary;

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
     * Set transactionIdServiceProvider.
     *
     * @param string $transactionIdServiceProvider
     *
     * @return Transaction
     */
    public function setTransactionIdServiceProvider($transactionIdServiceProvider)
    {
        $this->transactionIdServiceProvider = $transactionIdServiceProvider;

        return $this;
    }

    /**
     * Get transactionIdServiceProvider.
     *
     * @return string
     */
    public function getTransactionIdServiceProvider()
    {
        return $this->transactionIdServiceProvider;
    }

    /**
     * Set amountsent.
     *
     * @param float $amountsent
     *
     * @return Transaction
     */
    public function setAmountsent($amountsent)
    {
        $this->amountsent = $amountsent;

        return $this;
    }

    /**
     * Get amountsent.
     *
     * @return float
     */
    public function getAmountsent()
    {
        return $this->amountsent;
    }

    /**
     * Set statusServiceprovider.
     *
     * @param bool $statusServiceprovider
     *
     * @return Transaction
     */
    public function setStatusServiceprovider($statusServiceprovider)
    {
        $this->statusServiceprovider = $statusServiceprovider;

        return $this;
    }

    /**
     * Get statusServiceprovider.
     *
     * @return bool
     */
    public function getStatusServiceprovider()
    {
        return $this->statusServiceprovider;
    }

    /**
     * Set transactiontime.
     *
     * @param \DateTime $transactiontime
     *
     * @return Transaction
     */
    public function setTransactiontime($transactiontime)
    {
        $this->transactiontime = $transactiontime;

        return $this;
    }

    /**
     * Get transactiontime.
     *
     * @return \DateTime
     */
    public function getTransactiontime()
    {
        return $this->transactiontime;
    }

    /**
     * Set moneyReceived.
     *
     * @param float $moneyReceived
     *
     * @return Transaction
     */
    public function setMoneyReceived($moneyReceived)
    {
        $this->moneyReceived = $moneyReceived;

        return $this;
    }

    /**
     * Get moneyReceived.
     *
     * @return float
     */
    public function getMoneyReceived()
    {
        return $this->moneyReceived;
    }

    /**
     * Set pickupdate.
     *
     * @param \DateTime $pickupdate
     *
     * @return Transaction
     */
    public function setPickupdate($pickupdate)
    {
        $this->pickupdate = $pickupdate;

        return $this;
    }

    /**
     * Get pickupdate.
     *
     * @return \DateTime
     */
    public function getPickupdate()
    {
        return $this->pickupdate;
    }

    /**
     * Set financialServiceConfiguration.
     *
     * @param \TransactionBundle\Entity\FinancialServiceConfiguration|null $financialServiceConfiguration
     *
     * @return Transaction
     */
    public function setFinancialServiceConfiguration(\TransactionBundle\Entity\FinancialServiceConfiguration $financialServiceConfiguration = null)
    {
        $this->financialServiceConfiguration = $financialServiceConfiguration;

        return $this;
    }

    /**
     * Get financialServiceConfiguration.
     *
     * @return \TransactionBundle\Entity\FinancialServiceConfiguration|null
     */
    public function getFinancialServiceConfiguration()
    {
        return $this->financialServiceConfiguration;
    }

    /**
     * Set commodityDistributionBeneficiary.
     *
     * @param \DistributionBundle\Entity\CommodityDistributionBeneficiary|null $commodityDistributionBeneficiary
     *
     * @return Transaction
     */
    public function setCommodityDistributionBeneficiary(\DistributionBundle\Entity\CommodityDistributionBeneficiary $commodityDistributionBeneficiary = null)
    {
        $this->commodityDistributionBeneficiary = $commodityDistributionBeneficiary;

        return $this;
    }

    /**
     * Get commodityDistributionBeneficiary.
     *
     * @return \DistributionBundle\Entity\CommodityDistributionBeneficiary|null
     */
    public function getCommodityDistributionBeneficiary()
    {
        return $this->commodityDistributionBeneficiary;
    }
}
