<?php

declare(strict_types=1);

namespace Component\Assistance\Scoring;

use Entity\Household;

class SimpleCalculations
{
    public function numberOfChildrenInHousehold(Household $household): int|string|null
    {
        $children = 0;

        foreach ($household->getBeneficiaries() as $member) {
            if ($member->getAge() != null && $member->getAge() < 18) {
                $children++;
            }
        }

        return $children;
    }

    public function incomePerMember(Household $household): int|string|null
    {
        if ($household->getIncome() == null) {
            return null;
        }

        return $household->getIncome() / $household->getBeneficiaries()->count();
    }
}
