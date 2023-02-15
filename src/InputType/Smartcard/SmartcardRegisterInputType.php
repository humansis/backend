<?php

declare(strict_types=1);

namespace InputType\Smartcard;

use DateTime;
use Happyr\Validator\Constraint\EntityExist;
use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints\Iso8601;

class SmartcardRegisterInputType implements InputTypeInterface
{
    #[Assert\NotNull]
    #[Assert\Type(type: 'string')]
    private ?string $serialNumber = null;

    /**
     * @EntityExist(entity="Entity\Beneficiary")
     */
    #[Assert\NotNull]
    #[Assert\Type(type: 'int')]
    private int $beneficiaryId;

    #[Iso8601]
    private ?DateTime $createdAt;

    public static function create(string $serialNumber, int $beneficiaryId, DateTime $createdAt): self
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

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
