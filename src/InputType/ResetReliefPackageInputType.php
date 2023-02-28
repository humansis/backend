<?php

declare(strict_types=1);

namespace InputType;

use Happyr\Validator\Constraint\EntityExist;
use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ResetReliefPackageInputType implements InputTypeInterface
{
    /**
     * @EntityExist(entity="Entity\Assistance")
     */
    #[Assert\Type('integer')]
    #[Assert\NotNull]
    #[Assert\GreaterThan(0)]
    private int $assistanceId;

    /**
     * @EntityExist(entity="Entity\Beneficiary")
     */
    #[Assert\Type("integer")]
    #[Assert\NotNull]
    #[Assert\GreaterThan(0)]
    private int $beneficiaryId;

    #[Assert\NotBlank]
    #[Assert\Type('string')]
    private string $smartcardCode;

    /**
     * @EntityExist(entity="Entity\SmartcardDeposit")
     */
    #[Assert\Type("integer")]
    #[Assert\NotBlank(allowNull: true)]
    #[Assert\GreaterThan(0)]
    private ?int $depositId = null;

    private ?string $note = null;

    public function getAssistanceId(): int
    {
        return $this->assistanceId;
    }

    public function setAssistanceId(int $assistanceId): void
    {
        $this->assistanceId = $assistanceId;
    }

    public function getBeneficiaryId(): int
    {
        return $this->beneficiaryId;
    }

    public function setBeneficiaryId(int $beneficiaryId): void
    {
        $this->beneficiaryId = $beneficiaryId;
    }

    public function getSmartcardCode(): string
    {
        return $this->smartcardCode;
    }

    public function setSmartcardCode(string $smartcardCode): void
    {
        $this->smartcardCode = strtoupper($smartcardCode);
    }

    public function getDepositId(): ?int
    {
        return $this->depositId;
    }

    public function setDepositId(?int $depositId): void
    {
        $this->depositId = $depositId;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }
}
