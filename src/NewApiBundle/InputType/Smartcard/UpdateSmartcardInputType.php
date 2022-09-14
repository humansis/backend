<?php

namespace NewApiBundle\InputType\Smartcard;

use NewApiBundle\Request\InputTypeInterface;

class UpdateSmartcardInputType extends ChangeSmartcardInputType implements InputTypeInterface
{
    /**
     * @var bool
     */
    private $suspicious = false;

    /**
     * @var string|null
     */
    private $suspiciousReason;

    /**
     * @return bool
     */
    public function isSuspicious(): bool
    {
        return $this->suspicious;
    }

    /**
     * @param bool $suspicious
     */
    public function setSuspicious(bool $suspicious): void
    {
        $this->suspicious = $suspicious;
    }

    /**
     * @return string|null
     */
    public function getSuspiciousReason(): ?string
    {
        return $this->suspiciousReason;
    }

    /**
     * @param string|null $suspiciousReason
     */
    public function setSuspiciousReason(?string $suspiciousReason): void
    {
        $this->suspiciousReason = $suspiciousReason;
    }


}
