<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\ValueObject;

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
    private $amountDuplicities = 0;

    /**
     * @var integer
     */
    private $amountDuplicitiesResolved = 0;

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
    public function getAmountDuplicities(): int
    {
        return $this->amountDuplicities;
    }

    /**
     * @param int $amountDuplicities
     */
    public function setAmountDuplicities(int $amountDuplicities): void
    {
        $this->amountDuplicities = $amountDuplicities;
    }

    /**
     * @return int
     */
    public function getAmountDuplicitiesResolved(): int
    {
        return $this->amountDuplicitiesResolved;
    }

    /**
     * @param int $amountDuplicitiesResolved
     */
    public function setAmountDuplicitiesResolved(int $amountDuplicitiesResolved): void
    {
        $this->amountDuplicitiesResolved = $amountDuplicitiesResolved;
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
}
