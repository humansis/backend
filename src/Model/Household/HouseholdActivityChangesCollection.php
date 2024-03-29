<?php

namespace Model\Household;

use ArrayIterator;
use Entity\HouseholdActivity;
use IteratorAggregate;
use Model\Household\HouseholdChange\Factory\HouseholdChangeFactoryInterface;
use Model\Household\HouseholdChange\Factory\SimpleHouseholdChangeFactory;
use JsonSerializable;

class HouseholdActivityChangesCollection implements JsonSerializable, IteratorAggregate
{
    private readonly \Model\Household\HouseholdChange\Factory\HouseholdChangeFactoryInterface $factory;

    /**
     * @param HouseholdActivity[] $collection list of household activities
     */
    public function __construct(private $collection, HouseholdChangeFactoryInterface $factory = null)
    {
        $this->factory = $factory ?? new SimpleHouseholdChangeFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $data = [];

        $prev = null;
        foreach ($this->collection as $item) {
            if (null === $prev) { // Skip first activity. There is nothing to compare.
                $prev = $item;
                continue;
            }

            $change = $this->factory->create($item, $prev);

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
    public function getIterator(): \Traversable
    {
        return new ArrayIterator($this->jsonSerialize());
    }
}
