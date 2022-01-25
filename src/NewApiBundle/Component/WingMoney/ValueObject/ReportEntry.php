<?php
declare(strict_types=1);

namespace NewApiBundle\Component\WingMoney\ValueObject;

use DateTime;
use Symfony\Component\Validator\Constraints as Assert;

class ReportEntry
{
    /**
     * @var DateTime
     *
     * @Assert\NotNull
     * @Assert\DateTime
     */
    private $transactionDate;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @Assert\Type("string")
     */
    private $transactionId;

    /**
     * @var float|int
     *
     * @Assert\Type("numeric")
     * @Assert\NotNull
     */
    private $amount;

    /**
     * @var string
     *
     * @Assert\NotNull
     * @Assert\Type("string")
     */
    private $currency;

    /**
     * @var string
     *
     * @Assert\Type("digit")
     * @Assert\NotNull
     */
    private $phoneNumber;

    /**
     * @return DateTime
     */
    public function getTransactionDate(): DateTime
    {
        return $this->transactionDate;
    }

    /**
     * @param mixed $transactionDate
     */
    public function setTransactionDate($transactionDate): void
    {
        $this->transactionDate = $transactionDate;
    }

    /**
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    /**
     * @param mixed $transactionId
     */
    public function setTransactionId($transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return (float) $this->amount;
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    /**
     * @param string $phoneNumber
     */
    public function setPhoneNumber(string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }
}
