<?php

declare(strict_types=1);

namespace DTO;

class AssistanceBeneficiaryDTO
{
    /**
     * @var int[]
     */
    private array $reliefPackageIds = [];

    public function __construct(
        private readonly int $id,
        private readonly int $beneficiaryId,
        private readonly bool $removed,
        private readonly string|null $justification,
        string|null $reliefPackageIds,
    ) {
        if (!is_null($reliefPackageIds)) {
            $this->reliefPackageIds = array_map('intval', explode(',', $reliefPackageIds));
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBeneficiaryId(): int
    {
        return $this->beneficiaryId;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function getJustification(): string|null
    {
        return $this->justification;
    }

    /**
     * @return int[]
     */
    public function getReliefPackageIds(): array
    {
        return $this->reliefPackageIds;
    }
}
