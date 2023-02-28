<?php

declare(strict_types=1);

namespace Component\Import\ValueObject;

class ImportStatisticsValueObject
{
    private int $totalEntries = 0;

    private int $amountIntegrityCorrect = 0;

    private int $amountIntegrityFailed = 0;

    private int $amountIdentityDuplicities = 0;

    private int $amountIdentityDuplicitiesResolved = 0;

    private int $amountSimilarityDuplicities = 0;

    private int $amountSimilarityDuplicitiesResolved = 0;

    private int $amountEntriesToImport = 0;

    private ?string $status = null;

    public function getTotalEntries(): int
    {
        return $this->totalEntries;
    }

    public function setTotalEntries(int $totalEntries): void
    {
        $this->totalEntries = $totalEntries;
    }

    public function getAmountIntegrityCorrect(): int
    {
        return $this->amountIntegrityCorrect;
    }

    public function setAmountIntegrityCorrect(int $amountIntegrityCorrect): void
    {
        $this->amountIntegrityCorrect = $amountIntegrityCorrect;
    }

    public function getAmountIntegrityFailed(): int
    {
        return $this->amountIntegrityFailed;
    }

    public function setAmountIntegrityFailed(int $amountIntegrityFailed): void
    {
        $this->amountIntegrityFailed = $amountIntegrityFailed;
    }

    public function getAmountIdentityDuplicities(): int
    {
        return $this->amountIdentityDuplicities;
    }

    public function setAmountIdentityDuplicities(int $amountIdentityDuplicities): void
    {
        $this->amountIdentityDuplicities = $amountIdentityDuplicities;
    }

    public function getAmountIdentityDuplicitiesResolved(): int
    {
        return $this->amountIdentityDuplicitiesResolved;
    }

    public function setAmountIdentityDuplicitiesResolved(int $amountIdentityDuplicitiesResolved): void
    {
        $this->amountIdentityDuplicitiesResolved = $amountIdentityDuplicitiesResolved;
    }

    public function getAmountEntriesToImport(): int
    {
        return $this->amountEntriesToImport;
    }

    public function setAmountEntriesToImport(int $amountEntriesToImport): void
    {
        $this->amountEntriesToImport = $amountEntriesToImport;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getAmountSimilarityDuplicities(): int
    {
        return $this->amountSimilarityDuplicities;
    }

    public function setAmountSimilarityDuplicities(int $amountSimilarityDuplicities): void
    {
        $this->amountSimilarityDuplicities = $amountSimilarityDuplicities;
    }

    public function getAmountSimilarityDuplicitiesResolved(): int
    {
        return $this->amountSimilarityDuplicitiesResolved;
    }

    public function setAmountSimilarityDuplicitiesResolved(int $amountSimilarityDuplicitiesResolved): void
    {
        $this->amountSimilarityDuplicitiesResolved = $amountSimilarityDuplicitiesResolved;
    }
}
