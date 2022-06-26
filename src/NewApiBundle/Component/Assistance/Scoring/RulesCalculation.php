<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring;

use BeneficiaryBundle\Entity\Household;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringRule;

/**
 * All methods needs to be function(Household $household, ScoringRule $rule): int
 *
 * @package NewApiBundle\Component\Assistance\Scoring
 */
final class RulesCalculation
{
    /**
     * @param Household $household
     * @param ScoringRule $rule
     *
     * @return int
     */
    public function dependencyRatio(Household $household, ScoringRule $rule): int
    {
        if ($household->getBeneficiaries()->count() === 2 ) {
            return $rule->getOptionByValue('1 (mid.)')->getScore();
        } else if ($household->getBeneficiaries()->count() > 2) {
            return $rule->getOptionByValue('>1 (hight dep.)')->getScore();
        }

        return 0;
    }
}
