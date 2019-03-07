<?php

namespace TransactionBundle\Entity;

use DistributionBundle\Entity\DistributionBeneficiary;
use UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Type as JMS_Type;
use JMS\Serializer\Annotation\Groups;

/**
 * Transaction
 *
 * @ORM\Table(name="transaction")
 * @ORM\Entity(repositoryClass="TransactionBundle\Repository\TransactionRepository")
 */
class Transaction
{
    /**
     * Transaction status 
     * @var boolean
     */
     const FAILURE = 0;
     const SUCCESS = 1;
     const NO_PHONE = 2;
    
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"ValidatedDistribution"})
     * 
     */
    private $id;
    
    /**
     * @var string
     *
     * @ORM\Column(name="transaction_id", type="string", length=45)
     *
     * @Groups({"ValidatedDistribution"})
     */
    private $transactionId;

    /**
     * @var string
     *
     * @ORM\Column(name="amount_sent", type="string")
     *
     * @Groups({"ValidatedDistribution"})
     */
    private $amountSent;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_sent", type="datetime")
     *
     * @Groups({"ValidatedDistribution"})
     */
    private $dateSent;

    /**
     * @var int
     *
     * @ORM\Column(name="transaction_status", type="smallint")
     *
     * @Groups({"ValidatedDistribution", "FullReceivers", "FullDistribution"})
     */
    private $transactionStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="string", length=255, nullable=true)
     *
     * @Groups({"ValidatedDistribution"})
     */
    private $message;

    /**
     * @var float
     *
     * @ORM\Column(name="money_received", type="boolean", nullable=true)
     *
     * @Groups({"ValidatedDistribution"})
     */
    private $moneyReceived;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="pickup_date", type="datetime", nullable=true)
     *
     * @Groups({"ValidatedDistribution"})
     */
    private $pickupDate;

    /**
     * @var DistributionBeneficiary
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\DistributionBeneficiary", inversedBy="transactions", cascade={"persist"})
     */
    private $distributionBeneficiary;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="updated_on", type="datetime", nullable=true)
     * @JMS_Type("DateTime<'Y-m-d H:m:i'>")
     * 
     * @Groups({"ValidatedDistribution"})
     */
    private $updatedOn;
    
    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User", inversedBy="transactions", cascade={"persist"})
     */
    private $sentBy;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setUpdatedOn(new \DateTime());
    }

    /**
     * Get the value of Id 
     * 
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
 
    /**
     * Get the value of Transaction Id 
     * 
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }
 
    /** 
     * Set the value of Transaction Id 
     * 
     * @param string transactionId
     * 
     * @return self
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
 
        return $this;
    }
 
    /**
     * Get the value of Amount Sent 
     * 
     * @return string
     */
    public function getAmountSent()
    {
        return $this->amountSent;
    }
 
    /** 
     * Set the value of Amount Sent 
     * 
     * @param string amountSent
     * 
     * @return self
     */
    public function setAmountSent($amountSent)
    {
        $this->amountSent = $amountSent;
 
        return $this;
    }
 
    /**
     * Get the value of Date Sent 
     * 
     * @return \DateTime
     */
    public function getDateSent()
    {
        return $this->dateSent;
    }
 
    /** 
     * Set the value of Date Sent 
     * 
     * @param \DateTime dateSent
     * 
     * @return self
     */
    public function setDateSent(\DateTime $dateSent)
    {
        $this->dateSent = $dateSent;
 
        return $this;
    }
 
    /**
     * Get the value of Transaction Status 
     * 
     * @return int
     */
    public function getTransactionStatus()
    {
        return $this->transactionStatus;
    }
 
    /** 
     * Set the value of Transaction Status 
     * 
     * @param int transactionStatus
     * 
     * @return self
     */
    public function setTransactionStatus($transactionStatus)
    {
        $this->transactionStatus = $transactionStatus;
 
        return $this;
    }
 
    /**
     * Get the value of Message 
     * 
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
 
    /** 
     * Set the value of Message 
     * 
     * @param string message
     * 
     * @return self
     */
    public function setMessage($message)
    {
        $this->message = $message;
 
        return $this;
    }
 
    /**
     * Get the value of Money Received 
     * 
     * @return float
     */
    public function getMoneyReceived()
    {
        return $this->moneyReceived;
    }
 
    /** 
     * Set the value of Money Received 
     * 
     * @param float moneyReceived
     * 
     * @return self
     */
    public function setMoneyReceived($moneyReceived)
    {
        $this->moneyReceived = $moneyReceived;
 
        return $this;
    }
 
    /**
     * Get the value of Pickup Date 
     * 
     * @return \DateTime
     */
    public function getPickupDate()
    {
        return $this->pickupDate;
    }
 
    /** 
     * Set the value of Pickup Date 
     * 
     * @param \DateTime pickupDate
     * 
     * @return self
     */
    public function setPickupDate(\DateTime $pickupDate)
    {
        $this->pickupDate = $pickupDate;
 
        return $this;
    }
 

    /**
     * Get the value of Distribution Beneficiary 
     * 
     * @return DistributionBeneficiary
     */
    public function getDistributionBeneficiary()
    {
        return $this->distributionBeneficiary;
    }
 
    /** 
     * Set the value of Distribution Beneficiary 
     * 
     * @param DistributionBeneficiary distributionBeneficiary
     * 
     * @return self
     */
    public function setDistributionBeneficiary(DistributionBeneficiary $distributionBeneficiary)
    {
        $this->distributionBeneficiary = $distributionBeneficiary;
 
        return $this;
    }
 
    /**
    * Get the value of Sent By 
    * 
    * @return User
    */
    public function getSentBy()
    {
        return $this->sentBy;
    }
    
    /** 
    * Set the value of Sent By 
    * 
    * @param User sentBy
    * 
    * @return self
    */
    public function setSentBy(User $sentBy)
    {
        $this->sentBy = $sentBy;
        
        return $this;
    }
    
    /**
     * Get the value of Updated On 
     * 
     * @return \DateTime|null
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }
 
    /** 
     * Set the value of Updated On 
     * 
     * @param \DateTime|null updatedOn
     * 
     * @return self
     */
    public function setUpdatedOn($updatedOn)
    {
        $this->updatedOn = new \DateTime();
 
        return $this;
    }
}
