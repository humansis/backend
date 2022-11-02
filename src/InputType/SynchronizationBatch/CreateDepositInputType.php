<?php

declare(strict_types=1);

namespace InputType\SynchronizationBatch;

use DateTimeInterface;
use Request\InputTypeInterface;
use Utils\DateTime\Iso8601Converter;
use Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['CreateDepositInputType', 'Strict'])]
class CreateDepositInputType implements InputTypeInterface
{
    #[Assert\NotNull]
    #[Assert\Type(type: 'integer')]
    #[Assert\GreaterThan(value: 0)]
    private ?int $reliefPackageId = null;

    /**
     * @Iso8601
     */
    #[Assert\NotNull]
    #[Assert\NotBlank]
    private ?string $createdAt = null;

    #[Assert\Type(type: 'string')]
    #[Assert\Length(max: 14)]
    #[Assert\Regex(pattern: '/[A-Za-z0-9]+/')]
    private ?string $smartcardSerialNumber = null;

    /**
     * @var numeric
     */
    #[Assert\Type(type: 'numeric')]
    private $balanceBefore;

    /**
     * @var numeric
     */
    #[Assert\Type(type: 'numeric')]
    private $balanceAfter;

    /**
     * @return int
     */
    public function getReliefPackageId()
    {
        return $this->reliefPackageId;
    }

    /**
     * @param int $reliefPackageId
     */
    public function setReliefPackageId($reliefPackageId): void
    {
        $this->reliefPackageId = $reliefPackageId;
    }

    /**
     * @return DateTimeInterface
     */
    public function getCreatedAt()
    {
        return Iso8601Converter::toDateTime($this->createdAt);
    }

    /**
     * @param string $createdAt
     */
    public function setCreatedAt($createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getSmartcardSerialNumber(): string
    {
        return $this->smartcardSerialNumber;
    }

    public function setSmartcardSerialNumber(string $smartcardSerialNumber): void
    {
        $this->smartcardSerialNumber = $smartcardSerialNumber;
    }

    public function getBalanceBefore(): float|int|string
    {
        return $this->balanceBefore;
    }

    /**
     * @param float|int|string|null $balanceBefore
     */
    public function setBalanceBefore($balanceBefore): void
    {
        $this->balanceBefore = $balanceBefore;
    }

    public function getBalanceAfter(): float|int|string
    {
        return $this->balanceAfter;
    }

    public function setBalanceAfter(float|int|string $balanceAfter): void
    {
        $this->balanceAfter = $balanceAfter;
    }
}
