<?php

declare(strict_types=1);

namespace InputType\Smartcard;

use DateTimeImmutable;
use DateTimeInterface;
use Happyr\Validator\Constraint\EntityExist;
use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ManualDistributionInputType implements InputTypeInterface
{
    /**
     * @EntityExist(entity="Entity\Assistance\ReliefPackage")
     */
    #[Assert\NotBlank]
    private int $reliefPackageId;

    #[Assert\Type(type: 'float')]
    #[Assert\NotBlank(allowNull: true)]
    private float | null $value = null;

    #[Assert\NotBlank]
    #[Assert\Type(type: 'bool')]
    private bool $checkState = true;

    #[Assert\NotBlank]
    #[Assert\DateTime]
    private DateTimeImmutable $createdAt;

    /**
     * @EntityExist(entity="Entity\User")
     */
    #[Assert\NotBlank]
    private int $createdBy;

    /**
     * @EntityExist(entity="Entity\Smartcard")
     */
    #[Assert\NotBlank]
    private int $smartcardId;

    #[Assert\NotBlank(allowNull: true)]
    private float | null $spent = null;

    #[Assert\NotNull]
    #[Assert\Type(type: 'string')]
    private string $note = '';

    public function getReliefPackageId(): int
    {
        return $this->reliefPackageId;
    }

    public function setReliefPackageId(int $reliefPackageId): void
    {
        $this->reliefPackageId = $reliefPackageId;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(?float $value): void
    {
        $this->value = $value;
    }

    public function isCheckState(): bool
    {
        return $this->checkState;
    }

    public function setCheckState(bool $checkState): void
    {
        $this->checkState = $checkState;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $createdAt);
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(int $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    public function getSmartcardId(): int
    {
        return $this->smartcardId;
    }

    public function setSmartcardId(int $smartcardId): void
    {
        $this->smartcardId = $smartcardId;
    }

    public function getSpent(): ?float
    {
        return $this->spent;
    }

    public function setSpent(?float $spent): void
    {
        $this->spent = $spent;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): void
    {
        $this->note = $note;
    }
}
