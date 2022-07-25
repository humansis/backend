<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring;

use BeneficiaryBundle\Entity\Household;
use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringSupportedEnumsEnum;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringRule;

class RulesEnum
{
    public function getScore(Household $household, ScoringRule $rule): int
    {
        if (!in_array($rule->getFieldName(), ScoringSupportedEnumsEnum::values())) {
            throw new \InvalidArgumentException("Scoring rule {$rule->getTitle()} has unsupported enum {$rule->getFieldName()}.");
        }
        
        switch ($rule->getFieldName()) {
            case ScoringSupportedEnumsEnum::HOUSEHOLD_SHELTER_STATUS:
                $value = $household->getShelterStatus();
                break;
            default:
                return 0;
        }
        
        if ($value === null) {
            return 0;
        }

        try {
            return $rule->getOptionByValue($value)->getScore();
        } catch (\InvalidArgumentException $e) {
            return 0;
        }
    }
}