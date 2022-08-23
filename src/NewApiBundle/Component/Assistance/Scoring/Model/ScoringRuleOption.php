<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring\Model;

final class ScoringRuleOption
{
    /**
     * @var string
     */
    private $value;

    /**
     * @var integer
     */
    private $score;

    public function __construct(string $value, int $score)
    {
        $this->value = $value;
        $this->score = $score;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return int
     */
    public function getScore(): int
    {
        return $this->score;
    }
}

