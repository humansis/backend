<?php declare(strict_types=1);

namespace NewApiBundle\InputType\Smartcard;

use DateTimeInterface;
use NewApiBundle\Request\InputTypeInterface;

class DepositInputType implements InputTypeInterface
{
    /** @var string|null */
    private $serialNumber = null;

    /** @var int|null */
    private $beneficiaryId = null;

    /** @var int|null */
    private $assistanceId = null;

    /** @var int|null */
    private $reliefPackageId = null;

    private $value = null;

    private $balance = null;

    /** @var DateTimeInterface */
    private $createdAt;

    /**
     * @param string            $serialNumber
     * @param int               $reliefPackageId
     * @param                   $value
     * @param                   $balance
     * @param DateTimeInterface $createdAt
     *
     * @return DepositInputType
     */
    public static function createFromReliefPackage(
        string            $serialNumber,
        int               $reliefPackageId,
                          $value,
                          $balance,
        DateTimeInterface $createdAt
    ): DepositInputType {
        $self = new self();
        $self->setSerialNumber($serialNumber);
        $self->setReliefPackageId($reliefPackageId);
        $self->setValue($value);
        $self->setBalance($balance);
        $self->setCreatedAt($createdAt);

        return $self;
    }

    /**
     * @param string|null       $serialNumber
     * @param int               $beneficiaryId
     * @param int               $assistanceId
     * @param                   $value
     * @param                   $balanceBefore
     * @param DateTimeInterface $createdAt
     *
     * @return DepositInputType
     */
    public static function createFromAssistanceBeneficiary(
        ?string           $serialNumber,
        int               $beneficiaryId,
        int               $assistanceId,
                          $value,
                          $balanceBefore,
        DateTimeInterface $createdAt
    ): DepositInputType {
        $self = new self();
        $self->setSerialNumber($serialNumber);
        $self->setBeneficiaryId($beneficiaryId);
        $self->setAssistanceId($assistanceId);
        $self->setValue($value);
        $self->setBalance($balanceBefore);
        $self->setCreatedAt($createdAt);

        return $self;
    }

    /**
     * @return string|null
     */
    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    /**
     * @param string|null $serialNumber
     */
    public function setSerialNumber(?string $serialNumber): void
    {
        $this->serialNumber = $serialNumber;
    }

    /**
     * @return int|null
     */
    public function getBeneficiaryId(): ?int
    {
        return $this->beneficiaryId;
    }

    /**
     * @param int|null $beneficiaryId
     */
    public function setBeneficiaryId(?int $beneficiaryId): void
    {
        $this->beneficiaryId = $beneficiaryId;
    }

    /**
     * @return int|null
     */
    public function getAssistanceId(): ?int
    {
        return $this->assistanceId;
    }

    /**
     * @param int|null $assistanceId
     */
    public function setAssistanceId(?int $assistanceId): void
    {
        $this->assistanceId = $assistanceId;
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
