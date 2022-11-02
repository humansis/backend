<?php

namespace InputType\Smartcard;

use Request\InputTypeInterface;

class UpdateSmartcardInputType extends ChangeSmartcardInputType implements InputTypeInterface
{
    private bool $suspicious = false;

    private ?string $suspiciousReason = null;

    public function isSuspicious(): bool
    {
        return $this->suspicious;
    }

    public function setSuspicious(bool $suspicious): void
    {
        $this->suspicious = $suspicious;
    }

    public function getSuspiciousReason(): ?string
    {
        return $this->suspiciousReason;
    }

    public function setSuspiciousReason(?string $suspiciousReason): void
    {
        $this->suspiciousReason = $suspiciousReason;
    }
}
