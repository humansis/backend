<?php

namespace Entity;

use DateTime;
use Entity\AssistanceBeneficiary;
use Entity\Assistance\ReliefPackage;
use Entity\Helper\StandardizedPrimaryKey;
use Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Transaction
 *
 * @deprecated Mobile money transaction needs to be completely rewrite
 */
#[ORM\Table(name: 'transaction')]
#[ORM\Entity(repositoryClass: 'Repository\TransactionRepository')]
class Transaction
{
    use StandardizedPrimaryKey;

    /**
     * Transaction status
     *
     * @var bool
     */
    final public const FAILURE = 0;
    final public const SUCCESS = 1;
    final public const NO_PHONE = 2;
    final public const CANCELED = 3;

    public static function statuses()
    {
        return [
            self::FAILURE => 'Failure',
            self::SUCCESS => 'Success',
            self::NO_PHONE => 'No Phone',
            self::CANCELED => 'Canceled',
        ];
    }

    #[ORM\ManyToOne(targetEntity: 'Entity\Assistance\ReliefPackage')]
    #[ORM\JoinColumn(name: 'relief_package_id')]
    private ?\Entity\Assistance\ReliefPackage $reliefPackage = null;

    /**
     * @var string
     */
    #[SymfonyGroups(['ValidatedAssistance'])]
    #[ORM\Column(name: 'transaction_id', type: 'string', length: 45)]
    private $transactionId;

    /**
     * @var string
     */
    #[SymfonyGroups(['ValidatedAssistance'])]
    #[ORM\Column(name: 'amount_sent', type: 'string')]
    private $amountSent;

    #[SymfonyGroups(['ValidatedAssistance'])]
    #[ORM\Column(name: 'date_sent', type: 'datetime')]
    private ?\DateTime $dateSent = null;

    /**
     * @var int
     */
    #[SymfonyGroups(['ValidatedAssistance', 'FullReceivers', 'FullAssistance', 'SmallAssistance'])]
    #[ORM\Column(name: 'transaction_status', type: 'smallint')]
    private $transactionStatus;

    /**
     * @var string
     */
    #[SymfonyGroups(['ValidatedAssistance'])]
    #[ORM\Column(name: 'message', type: 'string', length: 255, nullable: true)]
    private $message;

    /**
     * @var bool
     */
    #[SymfonyGroups(['ValidatedAssistance'])]
    #[ORM\Column(name: 'money_received', type: 'boolean', nullable: true)]
    private $moneyReceived;

    #[SymfonyGroups(['ValidatedAssistance'])]
    #[ORM\Column(name: 'pickup_date', type: 'datetime', nullable: true)]
    private ?\DateTime $pickupDate = null;

    #[ORM\ManyToOne(targetEntity: 'Entity\AssistanceBeneficiary', cascade: ['persist'], inversedBy: 'transactions')]
    #[ORM\JoinColumn(name: 'distribution_beneficiary_id')]
    private ?\Entity\AssistanceBeneficiary $assistanceBeneficiary = null;

    #[SymfonyGroups(['ValidatedAssistance'])]
    #[ORM\Column(name: 'updated_on', type: 'datetime', nullable: true)]
    private ?\DateTime $updatedOn;

    #[ORM\ManyToOne(targetEntity: 'Entity\User', cascade: ['persist'], inversedBy: 'transactions')]
    private ?\Entity\User $sentBy = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setUpdatedOn(new DateTime());
    }

    public function getReliefPackage(): ?ReliefPackage
    {
        return $this->reliefPackage;
    }

    public function setReliefPackage(?ReliefPackage $reliefPackage): Transaction
    {
        $this->reliefPackage = $reliefPackage;

        return $this;
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
     * @return DateTime
     */
    public function getDateSent()
    {
        return $this->dateSent;
    }

    /**
     * Set the value of Date Sent
     *
     * @param DateTime dateSent
     *
     * @return self
     */
    public function setDateSent(DateTime $dateSent)
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
     * @return bool
     */
    public function getMoneyReceived(): ?bool
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
     * @return DateTime
     */
    public function getPickupDate()
    {
        return $this->pickupDate;
    }

    /**
     * Set the value of Pickup Date
     *
     * @param DateTime pickupDate
     *
     * @return self
     */
    public function setPickupDate(DateTime $pickupDate)
    {
        $this->pickupDate = $pickupDate;

        return $this;
    }

    /**
     * Get the value of Distribution Beneficiary
     *
     * @return AssistanceBeneficiary
     */
    public function getAssistanceBeneficiary()
    {
        return $this->assistanceBeneficiary;
    }

    /**
     * Set the value of Distribution Beneficiary
     *
     * @param AssistanceBeneficiary assistanceBeneficiary
     *
     * @return self
     */
    public function setAssistanceBeneficiary(AssistanceBeneficiary $assistanceBeneficiary)
    {
        $this->assistanceBeneficiary = $assistanceBeneficiary;

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
     * @return DateTime|null
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    /**
     * Set the value of Updated On
     *
     * @param DateTime|null updatedOn
     *
     * @return self
     */
    public function setUpdatedOn($updatedOn)
    {
        $this->updatedOn = new DateTime();

        return $this;
    }

    public function hasUpdatableStatus(): bool
    {
        return null === $this->moneyReceived
            || false === $this->moneyReceived
            || null === $this->pickupDate;
    }
}
