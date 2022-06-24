<?php

namespace NewApiBundle\Component\Assistance\Scoring;

use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Model\Vulnerability\Protocol;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringRule;

class Resolver
{
    /** @var array */
    private $scoringFiles;

    public function __construct(array $scoringFiles)
    {

    }

    public function compute(Household $household, string $scoringType): Protocol
    {

    }

    /**
     * @param string $scoringType
     * @return ScoringRule[]
     */
    private function getScoring(string $scoringType): array
    {

    }
}
