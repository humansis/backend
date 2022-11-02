<?php

declare(strict_types=1);

namespace InputType\Smartcard;

use DateTime;
use DateTimeInterface;
use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SmartcardRegisterInputType implements InputTypeInterface
{
    #[Assert\NotNull]
    #[Assert\Type(type: 'string')]
    private ?string $serialNumber = null;

    #[Assert\NotNull]
    #[Assert\Type(type: 'int')]
    private ?int $beneficiaryId = null;

    /**
     * @var DateTimeInterface
     */
    #[Assert\DateTime]
    private $createdAt;

    /**
     *
     * @return static
     */
    public static function create(string $serialNumber, int $beneficiaryId, string $createdAt): self
    {
        $self = new self();
        $self->setSerialNumber($serialNumber);
        $self->setBeneficiaryId($beneficiaryId);
        $self->setCreatedAt($createdAt);

        return $self;
    }

    public function getSerialNumber(): string
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(string $serialNumber): void
    {
        $this->serialNumber = strtoupper($serialNumber);
    }

    public function getBeneficiaryId(): int
    {
        return $this->beneficiaryId;
    }

    public function setBeneficiaryId(int $beneficiaryId): void
    {
        $this->beneficiaryId = $beneficiaryId;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = DateTime::createFromFormat('Y-m-d\TH:i:sO', $createdAt);
    }
}
