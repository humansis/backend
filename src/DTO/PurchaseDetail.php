<?php

declare(strict_types=1);

namespace DTO;

use DateTimeInterface;
use JsonSerializable;

class PurchaseDetail implements JsonSerializable
{
    /**
     * PurchaseDetail constructor.
     */
    public function __construct(private readonly DateTimeInterface $date, private readonly int $beneficiaryId, private readonly ?string $beneficiaryEnGivenName, private readonly ?string $beneficiaryEnFamilyName, private readonly ?string $beneficiaryLocalGivenName, private readonly ?string $beneficiaryLocalFamilyName, private readonly string $amount)
    {
    }

    public function getDate(): DateTimeInterface
    {
        return $this->date;
    }

    public function getBeneficiaryId(): int
    {
        return $this->beneficiaryId;
    }

    public function getBeneficiaryEnGivenName(): ?string
    {
        return $this->beneficiaryEnGivenName;
    }

    public function getBeneficiaryEnFamilyName(): ?string
    {
        return $this->beneficiaryEnFamilyName;
    }

    public function getBeneficiaryLocalGivenName(): ?string
    {
        return $this->beneficiaryLocalGivenName;
    }

    public function getBeneficiaryLocalFamilyName(): ?string
    {
        return $this->beneficiaryLocalFamilyName;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    private function concateName(?string $givenName, ?string $familyname): string
    {
        $names = [];
        if (!empty($givenName)) {
            $names[] = $givenName;
        }
        if (!empty($familyname)) {
            $names[] = $familyname;
        }

        return implode(' ', $names);
    }

    public function jsonSerialize()
    {
        return [
            'purchase_datetime' => $this->getDate()->format('U'),
            'purchase_date' => $this->getDate()->format('d-m-Y'),
            'purchase_amount' => (float) $this->getAmount(),
            'beneficiary_id' => $this->getBeneficiaryId(),
            'beneficiary_local_name' => $this->concateName(
                $this->getBeneficiaryLocalGivenName(),
                $this->getBeneficiaryLocalFamilyName()
            ),
            'beneficiary_en_name' => $this->concateName(
                $this->getBeneficiaryEnGivenName(),
                $this->getBeneficiaryEnFamilyName()
            ),
        ];
    }
}
