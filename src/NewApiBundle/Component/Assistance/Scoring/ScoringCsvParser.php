<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring;

use NewApiBundle\Model\AbstractCsvParser;
use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringCsvColumns;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringRule;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringRuleOption;

final class ScoringCsvParser extends AbstractCsvParser
{
    protected function mandatoryColumns(): array
    {
        return ScoringCsvColumns::values();
    }

    /**
     * @param array $csv
     *
     * @return ScoringRule[]
     */
    protected function processCsv(array $csv): array
    {
        /** @var ScoringRule[] $scoringRules */
        $scoringRules = [];

        $currentRule = null;

        foreach ($csv as $row) {
            if ($this->rowEmpty($row)) {
                continue;
            }

            if (!empty($row[ScoringCsvColumns::RULE_TYPE])) {
                if (!is_null($currentRule)) {
                    $scoringRules[] = $currentRule;
                }

                $currentRule = new ScoringRule($row[ScoringCsvColumns::RULE_TYPE], $row[ScoringCsvColumns::FIELD_NAME], $row[ScoringCsvColumns::TITLE]);
            }

            $currentRule->addOption(new ScoringRuleOption($row[ScoringCsvColumns::OPTIONS], (integer) $row[ScoringCsvColumns::POINTS]));
        }

        if (!is_null($currentRule)) {
            $scoringRules[] = $currentRule;
        }


        return $scoringRules;
    }
}
