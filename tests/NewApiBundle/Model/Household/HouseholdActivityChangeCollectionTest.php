<?php

namespace Tests\NewApiBundle\Model\Household;

use NewApiBundle\Entity\Household;
use NewApiBundle\Entity\HouseholdActivity;
use NewApiBundle\Model\Household\HouseholdActivityChangesCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use NewApiBundle\Entity\User;

class HouseholdActivityChangeCollectionTest extends KernelTestCase
{
    public function testChangesGenerated()
    {
        $collection = new HouseholdActivityChangesCollection([
            new HouseholdActivity(new Household(), new User(), '{"livelihood": 0, "notes": "init"}'),
            new HouseholdActivity(new Household(), new User(), '{"livelihood": 2, "notes": "xxx"}'),
            new HouseholdActivity(new Household(), new User(), '{"livelihood": 2, "notes": "xxx"}'),
            new HouseholdActivity(new Household(), new User(), '{"livelihood": 2, "notes": "yyy"}'),
        ]);

        // we want to check only changes in collection
        $resultChanges = [];
        foreach ($collection as $item) {
            $resultChanges[] = $item->getChanges();
        }

        $this->assertSame([
            ['livelihood' => 2, 'notes' => 'xxx'],
            ['notes' => 'yyy'],
        ], $resultChanges);
    }
}
