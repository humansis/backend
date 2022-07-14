<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\ValueObject;

use NewApiBundle\Entity;
use NewApiBundle\InputType\HouseholdCreateInputType;

class HouseholdCompare
{
    /**
     * @var HouseholdCreateInputType
     */
    private $imported;

    /**
     * @var Entity\Household
     */
    private $current;

    /**
     * @param HouseholdCreateInputType $imported
     * @param Entity\Household         $current
     */
    public function __construct(HouseholdCreateInputType $imported, Entity\Household $current)
    {
        $this->imported = $imported;
        $this->current = $current;
    }

    /**
     * @return HouseholdCreateInputType
     */
    public function getImported(): HouseholdCreateInputType
    {
        return $this->imported;
    }

    /**
     * @return Entity\Household
     */
    public function getCurrent(): Entity\Household
    {
        return $this->current;
    }


}
