<?php

declare(strict_types=1);

namespace NewApiBundle\InputType\SynchronizationBatch;

use NewApiBundle\Request\InputTypeInterface;
use NewApiBundle\Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"CreateDepositInputType", "Strict"})
 */
class CreateDepositInputType implements InputTypeInterface
{
    /**
     * @var integer
     *
     * @Assert\NotNull
     * @Assert\Type(type="integer")
     * @Assert\GreaterThan(value="0")
     */
    private $reliefPackageId;

    /**
     * @var string
     *
     * @Iso8601
     * @Assert\NotNull
     * @Assert\NotBlank
     */
    private $createdAt;

    /**
     * @var string
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min="5", max="10")
     * @Assert\Regex(pattern="/[A-Za-z0-9]+/")
     */
    private $smartcardSerialNumber;

    /**
     * @var numeric
     *
     * @Assert\Type(type="numeric")
     */
    private $balanceBefore;

    /**
     * @var numeric
     *
     * @Assert\Type(type="numeric")
     */
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
     * @return \DateTimeInterface
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTimeInterface $createdAt
     */
    public function setCreatedAt($createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return string
     */
    public function getSmartcardSerialNumber(): string
    {
        return $this->smartcardSerialNumber;
    }

    /**
     * @param string $smartcardSerialNumber
     */
    public function setSmartcardSerialNumber(string $smartcardSerialNumber): void
    {
        $this->smartcardSerialNumber = $smartcardSerialNumber;
    }

    /**
     * @return float|int|string
     */
    public function getBalanceBefore()
    {
        return $this->balanceBefore;
    }

    /**
     * @param float|int|string $balanceBefore
     */
    public function setBalanceBefore($balanceBefore): void
    {
        $this->balanceBefore = $balanceBefore;
    }

    /**
     * @return float|int|string
     */
    public function getBalanceAfter()
    {
        return $this->balanceAfter;
    }

    /**
     * @param float|int|string $balanceAfter
     */
    public function setBalanceAfter($balanceAfter): void
    {
        $this->balanceAfter = $balanceAfter;
    }

}
