<?php

namespace NewApiBundle\Model\Household\HouseholdChange\Factory;

use NewApiBundle\Entity\HouseholdActivity;
use NewApiBundle\Model\Household\HouseholdChange\SimpleHouseholdChange;

class SimpleHouseholdChangeFactory implements HouseholdChangeFactoryInterface
{
    /**
     * @param HouseholdActivity $new
     * @param HouseholdActivity $old
     * @return SimpleHouseholdChange
     */
    public function create(HouseholdActivity $new, HouseholdActivity $old)
    {
        return new SimpleHouseholdChange($new, $old);
    }
}
