<?php

namespace BeneficiaryBundle\Model\Household;

use BeneficiaryBundle\Entity\HouseholdActivity;
use JsonSerializable;

class HouseholdActivityChangesCollection implements JsonSerializable, \IteratorAggregate
{
    /**
     * @var HouseholdActivity[]
     */
    private $collection;

    /**
     * @param HouseholdActivity[] $collection list of household activities
     */
    public function __construct($collection)
    {
        $this->collection = $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $data = [];

        $prev = null;
        foreach ($this->collection as $item) {
            if (null === $prev) {
                $prev = $item;
                continue;
            }

            $change = new HouseholdActivityChange($item, $prev);

            // in result will not be items without any real change
            if ([] !== $change->getChanges()) {
                $data[] = $change;
            }

            $prev = $item;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->jsonSerialize());
    }
}
