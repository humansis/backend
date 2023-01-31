<?php

declare(strict_types=1);

namespace InputType;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ResetingReliefPackageInputType implements InputTypeInterface
{
     #[Assert\Type('integer')]
     #[Assert\NotNull]
     #[Assert\GreaterThan(0)]
    private int $assistanceId;

     #[Assert\Type("integer")]
     #[Assert\NotNull]
     #[Assert\GreaterThan(0)]
    private int $beneficiaryId;

     #[Assert\NotBlank]
     #[Assert\Type('string')]
    private string $smartcardCode;


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
}
