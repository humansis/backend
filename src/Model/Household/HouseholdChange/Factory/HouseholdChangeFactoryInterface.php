<?php

namespace Model\Household\HouseholdChange\Factory;

use Entity\HouseholdActivity;
use Model\Household\HouseholdChange\AbstractHouseholdChange;

interface HouseholdChangeFactoryInterface
{
    /**
     * @param HouseholdActivity $new
     * @param HouseholdActivity $old
     * @return AbstractHouseholdChange
     */
    public function create(HouseholdActivity $new, HouseholdActivity $old);
}
