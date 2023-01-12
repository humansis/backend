<?php

declare(strict_types=1);

namespace Component\Assistance\Scoring\Model;

use JsonException;
use Serializable;

final class ScoringProtocol
{
    /** @var float[] */
    private array $score = [];

    /**
     * @var float|null
     */
    private $totalScore = null;

    public function addScore(string $ruleTitle, float $score)
    {
        if (isset($this->score[$ruleTitle])) {
            $this->score[$ruleTitle] += $score;
        }

        $this->score[$ruleTitle] = $score;

        $this->totalScore = null;
    }

    public function getScore(string $ruleTitle): ?float
    {
        return $this->score[$ruleTitle] ?? null;
    }

    /**
     * @return float[] in format ['rule name' => <score_value> ]
     */
    public function getAllScores(): array
    {
        return $this->score;
    }

    public function getTotalScore(): float
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
     * @throws JsonException
     */
    public function serializeToJson(): string
    {
        return json_encode($this->score, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws JsonException
     */
    public static function unserializeFromJson(string $data): self
    {
        $scoringProtocol = new self();
        $scoringProtocol->score = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

        return $scoringProtocol;
    }
}
