<?php

declare(strict_types=1);

namespace DTO;

class BookletDTO
{
    /**
     * @var int[]
     */
    private array $voucherValues = [];

    public function __construct(
        private readonly int $id,
        private readonly string | null $code,
        private readonly string | null $currency,
        private readonly int | null $status,
        string | null $voucherValues,
    ) {
        if (!is_null($voucherValues)) {
            $this->voucherValues = array_map('intval', explode(',', $voucherValues));
        }
    }

    public function getId(): int | null
    {
        return $this->id;
    }

    public function getCode(): string | null
    {
        return $this->code;
    }

    public function getCurrency(): string | null
    {
        return $this->currency;
    }

    public function getStatus(): int | null
    {
        return $this->status;
    }

    /**
     * @return int[]
     */
    public function getVoucherValues(): array
    {
        return $this->voucherValues;
    }
}
