<?php

namespace BeneficiaryBundle\Model\Household\HouseholdChange\Factory;

use BeneficiaryBundle\Entity\HouseholdActivity;
use BeneficiaryBundle\Model\Household\HouseholdChange\FilteredHouseholdChange;

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
