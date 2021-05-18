<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\ValueObject;

class QueueProgressValueObject
{
    /**
     * @var integer
     */
    private $totalCount = 0;

    /**
     * @var integer
     */
    private $correct = 0;

    /**
     * @var integer
     */
    private $failed = 0;

    /**
     * @return int
     */
    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    /**
     * @param int $totalCount
     */
    public function setTotalCount(int $totalCount): void
    {
        $this->totalCount = $totalCount;
    }

    /**
     * @return int
     */
    public function getCorrect(): int
    {
        return $this->correct;
    }

    /**
     * @param int $correct
     */
    public function setCorrect(int $correct): void
    {
        $this->correct = $correct;
    }

    /**
     * @return int
     */
    public function getFailed(): int
    {
        return $this->failed;
    }

    /**
     * @param int $failed
     */
    public function setFailed(int $failed): void
    {
        $this->failed = $failed;
    }
}
