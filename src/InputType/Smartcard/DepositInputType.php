<?php

declare(strict_types=1);

namespace InputType\Smartcard;

use Happyr\Validator\Constraint\EntityExist;
use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints\Iso8601;

final class DepositInputType implements InputTypeInterface
{
    /**
     * @EntityExist(entity="Entity\Assistance\ReliefPackage")
     */
    #[Assert\NotBlank]
    private ?int $reliefPackageId = null;

    private $value = null;

    private $balance = null;

    #[Iso8601]
    private ?\DateTime $createdAt;

    public static function create(int $reliefPackageId, $value, $balance, \DateTime $createdAt): self
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

    public function setValue(mixed $value): void
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

    public function setBalance(mixed $balance): void
    {
        $this->balance = $balance;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getReliefPackageId(): ?int
    {
        return $this->reliefPackageId;
    }

    public function setReliefPackageId(?int $reliefPackageId): void
    {
        $this->reliefPackageId = $reliefPackageId;
    }
}
