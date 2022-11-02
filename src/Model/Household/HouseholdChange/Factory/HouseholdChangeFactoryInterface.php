<?php

namespace Model\Household\HouseholdChange\Factory;

use Entity\HouseholdActivity;
use Model\Household\HouseholdChange\AbstractHouseholdChange;

interface HouseholdChangeFactoryInterface
{
    /**
     * @return AbstractHouseholdChange
     */
    public function create(HouseholdActivity $new, HouseholdActivity $old);
}
