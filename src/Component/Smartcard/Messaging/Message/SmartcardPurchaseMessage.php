<?php

declare(strict_types=1);

namespace Component\Smartcard\Messaging\Message;

class SmartcardPurchaseMessage
{
    public function __construct(
        private readonly string | null $smartcardNumber = null,
        private readonly array $purchaseRequestBody = []
    ) {
    }

    public function getSmartcardNumber(): string | null
    {
        return $this->smartcardNumber;
    }

    public function getPurchaseRequestBody(): array
    {
        return $this->purchaseRequestBody;
    }
}
