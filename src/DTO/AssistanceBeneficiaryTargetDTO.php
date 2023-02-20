<?php

declare(strict_types=1);

namespace DTO;

use DateTimeInterface;

class AssistanceBeneficiaryTargetDTO
{
    /**
     * @var int[]
     */
    private array $reliefPackageIds = [];

    /**
     * @var int[]
     */
    private array $bookletIds = [];

    public function __construct(
        private readonly int $id,
        private readonly int $beneficiaryId,
        private readonly string $localFamilyName,
        private readonly string $localGivenName,
        private readonly string | null $referralType,
        private readonly string | null $referralComment,
        private readonly DateTimeInterface | null $distributedAt,
        private readonly string | null $smartcardSerialNumber,
        private readonly int $personId,
        string | null $reliefPackageIds,
        string | null $bookletIds
    ) {
        if (!is_null($reliefPackageIds)) {
            $this->reliefPackageIds = array_map('intval', explode(',', $reliefPackageIds));
        }

        if (!is_null($bookletIds)) {
            $this->bookletIds = array_map('intval', explode(',', $bookletIds));
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

    public function getLocalFamilyName(): string
    {
        return $this->localFamilyName;
    }

    public function getLocalGivenName(): string
    {
        return $this->localGivenName;
    }

    public function getReferralType(): string | null
    {
        return $this->referralType;
    }

    public function getReferralComment(): string | null
    {
        return $this->referralComment;
    }

    public function getDistributedAt(): DateTimeInterface | null
    {
        return $this->distributedAt;
    }

    public function getSmartcardSerialNumber(): string | null
    {
        return $this->smartcardSerialNumber;
    }

    public function getPersonId(): int
    {
        return $this->personId;
    }

    /**
     * @return int[]
     */
    public function getReliefPackageIds(): array
    {
        return $this->reliefPackageIds;
    }

    /**
     * @return int[]
     */
    public function getBookletIds(): array
    {
        return $this->bookletIds;
    }
}
