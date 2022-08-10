<?php

namespace NewApiBundle\Utils\Synchronization;

use DateTimeImmutable;
use DateTimeInterface;

class RequestDataRecord
{
    /**
     * @var DateTimeImmutable
     */
    private $createdAt;

    /**
     * @var float
     */
    private $balanceAfter;

    /**
     * @var float
     */
    private $balanceBefore;

    /**
     * @var int
     */
    private $reliefPackageId;

    /**
     * @var string
     */
    private $smartcardSerialNumber;

    /**
     * @param $createdAt
     * @param $balanceAfter
     * @param $balanceBefore
     * @param $reliefPackageId
     * @param $smartcardSerialNumber
     */
    public function __construct($createdAt, $balanceAfter, $balanceBefore, $reliefPackageId, $smartcardSerialNumber)
    {
        $this->createdAt = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $createdAt);
        $this->balanceAfter = $balanceAfter;
        $this->balanceBefore = $balanceBefore;
        $this->reliefPackageId = $reliefPackageId;
        $this->smartcardSerialNumber = $smartcardSerialNumber;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return float
     */
    public function getBalanceAfter(): float
    {
        return $this->balanceAfter;
    }

    /**
     * @return float
     */
    public function getBalanceBefore(): float
    {
        return $this->balanceBefore;
    }

    /**
     * @return int
     */
    public function getReliefPackageId(): int
    {
        return $this->reliefPackageId;
    }

    /**
     * @return string
     */
    public function getSmartcardSerialNumber(): string
    {
        return $this->smartcardSerialNumber;
    }





}