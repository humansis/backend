<?php

namespace Model\Household\HouseholdChange\Factory;

use Entity\HouseholdActivity;
use Model\Household\HouseholdChange\SimpleHouseholdChange;

class SimpleHouseholdChangeFactory implements HouseholdChangeFactoryInterface
{
    /**
     * @return SimpleHouseholdChange
     */
    public function create(HouseholdActivity $new, HouseholdActivity $old)
    {
        return new SimpleHouseholdChange($new, $old);
    }
}
