<?php

declare(strict_types=1);

namespace DTO;

use DateTimeInterface;

class ProjectDTO
{
    private bool $deletable;

    private array $sectors = [];

    private array $donorIds = [];

    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string|null $internalId,
        private readonly string $iso3,
        private readonly string|null $notes,
        private readonly float|null $target,
        private readonly DateTimeInterface $startDate,
        private readonly DateTimeInterface $endDate,
        string|null $sectors,
        string|null $donorIds,
        private readonly int $numberOfHouseholds,
        int $activeAssistanceCount,
        private readonly int $beneficiariesReached,
        private readonly string|null $projectInvoiceAddressLocal,
        private readonly string|null $projectInvoiceAddressEnglish,
        private readonly array $allowedProductCategoryTypes,
        private readonly DateTimeInterface $createdAt,
        private readonly DateTimeInterface $lastModifiedAt
    ) {
        $this->deletable = $activeAssistanceCount === 0;

        if (!is_null($sectors)) {
            $this->sectors = explode(',', $sectors);
        }

        if (!is_null($donorIds)) {
            $this->donorIds = array_map('intval', explode(',', $donorIds));
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getInternalId(): string|null
    {
        return $this->internalId;
    }

    public function getIso3(): string
    {
        return $this->iso3;
    }

    public function getNotes(): string|null
    {
        return $this->notes;
    }

    public function getTarget(): float|null
    {
        return $this->target;
    }

    public function getStartDate(): DateTimeInterface
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTimeInterface
    {
        return $this->endDate;
    }

    public function getSectors(): array
    {
        return $this->sectors;
    }

    public function getDonorIds(): array
    {
        return $this->donorIds;
    }

    public function getNumberOfHouseholds(): int
    {
        return $this->numberOfHouseholds;
    }

    public function isDeletable(): bool
    {
        return $this->deletable;
    }

    public function getBeneficiariesReached(): int
    {
        return $this->beneficiariesReached;
    }

    public function getProjectInvoiceAddressLocal(): string|null
    {
        return $this->projectInvoiceAddressLocal;
    }

    public function getProjectInvoiceAddressEnglish(): string|null
    {
        return $this->projectInvoiceAddressEnglish;
    }

    public function getAllowedProductCategoryTypes(): array
    {
        return $this->allowedProductCategoryTypes;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getLastModifiedAt(): DateTimeInterface
    {
        return $this->lastModifiedAt;
    }
}
