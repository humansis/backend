<?php

namespace Model\Household\HouseholdChange\Factory;

use Entity\HouseholdActivity;
use Model\Household\HouseholdChange\FilteredHouseholdChange;

class FilteredHouseholdChangeFactory implements HouseholdChangeFactoryInterface
{
    /**
     * @param HouseholdActivity $new
     * @param HouseholdActivity $old
     * @return FilteredHouseholdChange
     */
    public function create(HouseholdActivity $new, HouseholdActivity $old)
    {
        return new FilteredHouseholdChange($new, $old);
    }
}
