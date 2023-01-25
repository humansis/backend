<?php

declare(strict_types=1);

namespace DTO;

use DateTimeInterface;

class SmartcardPurchasedItemDTO
{
    public function __construct(
        private readonly int $householdId,
        private readonly int $beneficiaryId,
        private readonly int $projectId,
        private readonly int $assistanceId,
        private readonly int $locationId,
        private readonly DateTimeInterface $datePurchase,
        private readonly string $smartcardCode,
        private readonly int $productId,
        private readonly string $unit,
        private readonly string $value,
        private readonly string | null $currency,
        private readonly int $vendorId,
        private readonly string | null $invoiceNumber,
        private readonly string | null $contractNumber,
        private readonly string | null $idNumber,
        private readonly string $countryIso3
    ) {
    }

    public function getHouseholdId(): int
    {
        return $this->householdId;
    }

    public function getBeneficiaryId(): int
    {
        return $this->beneficiaryId;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getAssistanceId(): int
    {
        return $this->assistanceId;
    }

    public function getLocationId(): int
    {
        return $this->locationId;
    }

    public function getDatePurchase(): DateTimeInterface
    {
        return $this->datePurchase;
    }

    public function getSmartcardCode(): string
    {
        return $this->smartcardCode;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getCurrency(): string | null
    {
        return $this->currency;
    }

    public function getVendorId(): int
    {
        return $this->vendorId;
    }

    public function getInvoiceNumber(): string | null
    {
        return $this->invoiceNumber;
    }

    public function getContractNumber(): string | null
    {
        return $this->contractNumber;
    }

    public function getIdNumber(): string | null
    {
        return $this->idNumber;
    }

    public function getCountryIso3(): string
    {
        return $this->countryIso3;
    }
}
