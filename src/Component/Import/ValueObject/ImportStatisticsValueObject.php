<?php
declare(strict_types=1);

namespace Component\Import\ValueObject;

class ImportStatisticsValueObject
{
    /**
     * @var integer
     */
    private $totalEntries = 0;

    /**
     * @var integer
     */
    private $amountIntegrityCorrect = 0;

    /**
     * @var integer
     */
    private $amountIntegrityFailed = 0;

    /**
     * @var integer
     */
    private $amountIdentityDuplicities = 0;

    /**
     * @var integer
     */
    private $amountIdentityDuplicitiesResolved = 0;

    /**
     * @var integer
     */
    private $amountSimilarityDuplicities = 0;

    /**
     * @var integer
     */
    private $amountSimilarityDuplicitiesResolved = 0;

    /**
     * @var integer
     */
    private $amountEntriesToImport = 0;

    /**
     * @var string
     */
    private $status;

    /**
     * @return int
     */
    public function getTotalEntries(): int
    {
        return $this->totalEntries;
    }

    /**
     * @param int $totalEntries
     */
    public function setTotalEntries(int $totalEntries): void
    {
        $this->totalEntries = $totalEntries;
    }

    /**
     * @return int
     */
    public function getAmountIntegrityCorrect(): int
    {
        return $this->amountIntegrityCorrect;
    }

    /**
     * @param int $amountIntegrityCorrect
     */
    public function setAmountIntegrityCorrect(int $amountIntegrityCorrect): void
    {
        $this->amountIntegrityCorrect = $amountIntegrityCorrect;
    }

    /**
     * @return int
     */
    public function getAmountIntegrityFailed(): int
    {
        return $this->amountIntegrityFailed;
    }

    /**
     * @param int $amountIntegrityFailed
     */
    public function setAmountIntegrityFailed(int $amountIntegrityFailed): void
    {
        $this->amountIntegrityFailed = $amountIntegrityFailed;
    }

    /**
     * @return int
     */
    public function getAmountIdentityDuplicities(): int
    {
        return $this->amountIdentityDuplicities;
    }

    /**
     * @param int $amountIdentityDuplicities
     */
    public function setAmountIdentityDuplicities(int $amountIdentityDuplicities): void
    {
        $this->amountIdentityDuplicities = $amountIdentityDuplicities;
    }

    /**
     * @return int
     */
    public function getAmountIdentityDuplicitiesResolved(): int
    {
        return $this->amountIdentityDuplicitiesResolved;
    }

    /**
     * @param int $amountIdentityDuplicitiesResolved
     */
    public function setAmountIdentityDuplicitiesResolved(int $amountIdentityDuplicitiesResolved): void
    {
        $this->amountIdentityDuplicitiesResolved = $amountIdentityDuplicitiesResolved;
    }

    /**
     * @return int
     */
    public function getAmountEntriesToImport(): int
    {
        return $this->amountEntriesToImport;
    }

    /**
     * @param int $amountEntriesToImport
     */
    public function setAmountEntriesToImport(int $amountEntriesToImport): void
    {
        $this->amountEntriesToImport = $amountEntriesToImport;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getAmountSimilarityDuplicities(): int
    {
        return $this->amountSimilarityDuplicities;
    }

    /**
     * @param int $amountSimilarityDuplicities
     */
    public function setAmountSimilarityDuplicities(int $amountSimilarityDuplicities): void
    {
        $this->amountSimilarityDuplicities = $amountSimilarityDuplicities;
    }

    /**
     * @return int
     */
    public function getAmountSimilarityDuplicitiesResolved(): int
    {
        return $this->amountSimilarityDuplicitiesResolved;
    }

    /**
     * @param int $amountSimilarityDuplicitiesResolved
     */
    public function setAmountSimilarityDuplicitiesResolved(int $amountSimilarityDuplicitiesResolved): void
    {
        $this->amountSimilarityDuplicitiesResolved = $amountSimilarityDuplicitiesResolved;
    }
}
