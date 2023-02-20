<?php

declare(strict_types=1);

namespace DTO;

class NationalIdInfoDTO
{
    public function __construct(
        private readonly string $idType,
        private readonly string $idNumber
    ) {
    }

    public function getIdType(): string
    {
        return $this->idType;
    }

    public function getIdNumber(): string
    {
        return $this->idNumber;
    }
}
