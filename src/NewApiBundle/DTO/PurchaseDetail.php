<?php

declare(strict_types=1);

namespace NewApiBundle\DTO;

class PurchaseDetail implements \JsonSerializable
{
    /** @var \DateTimeInterface */
    private $date;

    /** @var int */
    private $beneficiaryId;

    /** @var string|null */
    private $beneficiaryEnGivenName;

    /** @var string|null */
    private $beneficiaryEnFamilyName;

    /** @var string|null */
    private $beneficiaryLocalGivenName;

    /** @var string|null */
    private $beneficiaryLocalFamilyName;

    /** @var float */
    private $amount;

    /**
     * PurchaseDetail constructor.
     *
     * @param \DateTimeInterface $date
     * @param int                $beneficiaryId
     * @param string|null        $beneficiaryEnGivenName
     * @param string|null        $beneficiaryEnFamilyName
     * @param string|null        $beneficiaryLocalGivenName
     * @param string|null        $beneficiaryLocalFamilyName
     * @param string             $amount
     */
    public function __construct(
        \DateTimeInterface $date,
        int $beneficiaryId,
        ?string $beneficiaryEnGivenName,
        ?string $beneficiaryEnFamilyName,
        ?string $beneficiaryLocalGivenName,
        ?string $beneficiaryLocalFamilyName,
        string $amount
    ) {
        $this->date = $date;
        $this->beneficiaryId = $beneficiaryId;
        $this->beneficiaryEnGivenName = $beneficiaryEnGivenName;
        $this->beneficiaryEnFamilyName = $beneficiaryEnFamilyName;
        $this->beneficiaryLocalGivenName = $beneficiaryLocalGivenName;
        $this->beneficiaryLocalFamilyName = $beneficiaryLocalFamilyName;
        $this->amount = $amount;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @return int
     */
    public function getBeneficiaryId(): int
    {
        return $this->beneficiaryId;
    }

    /**
     * @return string|null
     */
    public function getBeneficiaryEnGivenName(): ?string
    {
        return $this->beneficiaryEnGivenName;
    }

    /**
     * @return string|null
     */
    public function getBeneficiaryEnFamilyName(): ?string
    {
        return $this->beneficiaryEnFamilyName;
    }

    /**
     * @return string|null
     */
    public function getBeneficiaryLocalGivenName(): ?string
    {
        return $this->beneficiaryLocalGivenName;
    }

    /**
     * @return string|null
     */
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
