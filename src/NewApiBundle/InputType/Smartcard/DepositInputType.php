<?php declare(strict_types=1);

namespace NewApiBundle\InputType\Smartcard;

use DateTimeInterface;
use NewApiBundle\Request\InputTypeInterface;

final class DepositInputType implements InputTypeInterface
{

    /** @var int */
    private $reliefPackageId;

    private $value = null;

    private $balance = null;

    /** @var DateTimeInterface */
    private $createdAt;

    public static function create(int $reliefPackageId, $value, $balance, DateTimeInterface $createdAt): self
    {
        $self = new self();
        $self->reliefPackageId = $reliefPackageId;
        $self->createdAt = $createdAt;
        $self->value = $value;
        $self->balance = $balance;

        return $self;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * @param mixed $balance
     */
    public function setBalance($balance): void
    {
        $this->balance = $balance;
    }

    /**
     * @return DateTimeInterface
     */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @param DateTimeInterface $createdAt
     */
    public function setCreatedAt(DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return int|null
     */
    public function getReliefPackageId(): ?int
    {
        return $this->reliefPackageId;
    }

    /**
     * @param int|null $reliefPackageId
     */
    public function setReliefPackageId(?int $reliefPackageId): void
    {
        $this->reliefPackageId = $reliefPackageId;
    }

}