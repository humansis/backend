<?php

namespace BeneficiaryBundle\Model\Household\HouseholdChange;

use BeneficiaryBundle\Entity\HouseholdActivity;


/**
 * Class FilteredHouseholdChange allowes only defined fields to be shown as changed.
 */
class FilteredHouseholdChange extends AbstractHouseholdChange
{
    const ALLOWED_FIELDS = [
        'income',
        'debt_level',
        'food_consumption_score',
        'coping_strategies_index',
        'support_date_received',
    ];

    public function __construct(HouseholdActivity $activity, HouseholdActivity $previousActivity)
    {
        parent::__construct($activity, $previousActivity);
    }

    /**
     *
     */
    public function getChanges(): array
    {
        $diff = parent::getChanges();

        // only allowed fields can be shown
        foreach ($diff as $field => $value) {
            if (!in_array($field, self::ALLOWED_FIELDS)) {
                unset($diff[$field]);
            }
        }

        return $diff;
    }
}
