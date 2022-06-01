<?php declare(strict_types=1);

namespace NewApiBundle\InputType\Smartcard;

use DateTimeInterface;
use NewApiBundle\Request\InputTypeInterface;

class DepositInputType implements InputTypeInterface
{
    /** @var string|null */
    private $serialNumber = null;

    /** @var int */
    private $beneficiaryId = 0;

    /** @var int */
    private $assistanceId = 0;

    private $value = null;

    private $balanceBefore = null;

    /** @var DateTimeInterface */
    private $createdAt;

    /**
     * @param string|null       $serialNumber
     * @param int               $beneficiaryId
     * @param int               $assistanceId
     * @param $value
     * @param $balanceBefore
     * @param DateTimeInterface $createdAt
     *
     * @return DepositInputType
     */
    public static function create(?string $serialNumber, int $beneficiaryId, int $assistanceId, $value, $balanceBefore, DateTimeInterface $createdAt): DepositInputType
    {
        $self = new self();
        $self->setSerialNumber($serialNumber);
        $self->setBeneficiaryId($beneficiaryId);
        $self->setAssistanceId($assistanceId);
        $self->setValue($value);
        $self->setBalanceBefore($balanceBefore);
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
     * @return int
     */
    public function getBeneficiaryId(): int
    {
        return $this->beneficiaryId;
    }

    /**
     * @param int $beneficiaryId
     */
    public function setBeneficiaryId(int $beneficiaryId): void
    {
        $this->beneficiaryId = $beneficiaryId;
    }

    /**
     * @return int
     */
    public function getAssistanceId(): int
    {
        return $this->assistanceId;
    }

    /**
     * @param int $assistanceId
     */
    public function setAssistanceId(int $assistanceId): void
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
    public function getBalanceBefore()
    {
        return $this->balanceBefore;
    }

    /**
     * @param mixed $balanceBefore
     */
    public function setBalanceBefore($balanceBefore): void
    {
        $this->balanceBefore = $balanceBefore;
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

}
