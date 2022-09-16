<?php
declare(strict_types=1);

namespace Component\Assistance\Scoring\Model;

use \JsonException;

final class ScoringProtocol implements \Serializable
{
    /** @var int[] */
    private $score = [];

    /**
     * @var int|null
     */
    private $totalScore = null;

    public function addScore(string $ruleTitle, int $score)
    {
        if (isset($this->score[$ruleTitle])) {
            $this->score[$ruleTitle] += $score;
        }

        $this->score[$ruleTitle] = $score;

        $this->totalScore = null;
    }

    public function getScore(string $ruleTitle): ?int
    {
        return $this->score[$ruleTitle] ?? null;
    }

    /**
     * @return int[] in format ['rule name' => <score_value> ]
     */
    public function getAllScores(): array
    {
        return $this->score;
    }

    public function getTotalScore(): int
    {
        if (is_null($this->totalScore)) {
            $this->calculateTotalScore();
        }

        return $this->totalScore;
    }

    private function calculateTotalScore(): void
    {
        $totalScore = 0;

        foreach ($this->score as $value) {
            $totalScore += $value;
        }

        $this->totalScore = $totalScore;
    }

    /**
     * @return string
     * @throws JsonException
     */
    public function serialize(): string
    {
        return json_encode($this->score, JSON_THROW_ON_ERROR);
    }

    /**
     * @param string $data
     * @throws JsonException
     */
    public function unserialize($data): void
    {
        $this->score = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
    }
}
