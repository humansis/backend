<?php

declare(strict_types=1);

namespace Component\Smartcard\Messaging\Message;

class SmartcardDepositMessage
{
    public function __construct(
        private readonly int $userId,
        private readonly string $inputTypeClass,
        private readonly string | null $smartcardNumber = null,
        private readonly array | null $depositRequestBody = null
    ) {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getInputTypeClass(): string
    {
        return $this->inputTypeClass;
    }

    public function getSmartcardNumber(): string | null
    {
        return $this->smartcardNumber;
    }

    public function getDepositRequestBody(): array | null
    {
        return $this->depositRequestBody;
    }
}
