<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class Scoring
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var ScoringRule[]
     *
     * @Assert\All(
     *     constraints={
     *         @Assert\Valid()
     *     }
     * )
     */
    private $rules;

    /**
     * Scoring constructor
     * .
     * @param string $name
     * @param ScoringRule[] $rules
     */
    public function __construct(string $name, array $rules)
    {
        $this->name = $name;
        $this->rules = $rules;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return ScoringRule[]
     */
    public function getRules(): array
    {
        return $this->rules;
    }
}
