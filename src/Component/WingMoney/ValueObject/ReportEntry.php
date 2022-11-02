<?php

declare(strict_types=1);

namespace Component\WingMoney\ValueObject;

use DateTime;
use Symfony\Component\Validator\Constraints as Assert;

class ReportEntry
{
    #[Assert\NotNull]
    #[Assert\DateTime]
    private ?\DateTime $transactionDate = null;

    #[Assert\NotBlank]
    #[Assert\Type('string')]
    private ?string $transactionId = null;

    #[Assert\Type('numeric')]
    #[Assert\NotNull]
    private float|int|null $amount = null;

    #[Assert\NotNull]
    #[Assert\Type('string')]
    private ?string $currency = null;

    #[Assert\Type('digit')]
    #[Assert\NotNull]
    private ?string $phoneNumber = null;

    public function getTransactionDate(): DateTime
    {
        return $this->transactionDate;
    }

    public function setTransactionDate(mixed $transactionDate): void
    {
        $this->transactionDate = $transactionDate;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function setTransactionId(mixed $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    public function getAmount(): float
    {
        return (float) $this->amount;
    }

    public function setAmount(mixed $amount): void
    {
        $this->amount = $amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }
}
