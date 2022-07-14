<?php

namespace BeneficiaryBundle\Model\Household\HouseholdChange\Factory;

use NewApiBundle\Entity\HouseholdActivity;
use BeneficiaryBundle\Model\Household\HouseholdChange\AbstractHouseholdChange;

interface HouseholdChangeFactoryInterface
{
    /**
     * @param HouseholdActivity $new
     * @param HouseholdActivity $old
     * @return AbstractHouseholdChange
     */
    public function create(HouseholdActivity $new, HouseholdActivity $old);
}
