<?php

namespace Tests\Model\Household;

use Entity\Household;
use Entity\HouseholdActivity;
use Model\Household\HouseholdChange\SimpleHouseholdChange;
use PHPUnit\Framework\TestCase;
use Entity\User;

class HouseholdActivityChangeTest extends TestCase
{
    /**
     * @param HouseholdActivity $old
     * @param HouseholdActivity $new
     * @param array             $expectedChanges
     *
     * @dataProvider providerChanges
     */
    public function testChangesAreCreatedCorrectly($old, $new, $expectedChanges)
    {
        $object = new SimpleHouseholdChange($new, $old);

        $this->assertEquals($expectedChanges, $object->getChanges());
    }

    public function providerChanges()
    {
        $household = new Household();
        $author = new User();

        return [
            'no change' => [
                new HouseholdActivity($household, $author, '{"livelihood": 0, "notes": "aaa"}'),
                new HouseholdActivity($household, $author, '{"livelihood": 0, "notes": "aaa"}'),
                [],
            ],
            'simple change' => [
                new HouseholdActivity($household, $author, '{"livelihood": 0, "notes": "aaa"}'),
                new HouseholdActivity($household, $author, '{"livelihood": 1, "notes": "aaa"}'),
                ['livelihood' => 1],
            ],
            'multiple changes' => [
                new HouseholdActivity($household, $author, '{"livelihood": 0, "notes": "aaa"}'),
                new HouseholdActivity($household, $author, '{"livelihood": 1, "notes": "xxx"}'),
                ['livelihood' => 1, 'notes' => 'xxx'],
            ],
            'object to scalar change' => [
                new HouseholdActivity($household, $author, '{"livelihood": 0, "notes": {"a": 0}}'),
                new HouseholdActivity($household, $author, '{"livelihood": 0, "notes": "a"}'),
                ['notes' => 'a'],
            ],
            'scalar to array change' => [
                new HouseholdActivity($household, $author, '{"livelihood": 0, "notes": "a"}'),
                new HouseholdActivity($household, $author, '{"livelihood": 0, "notes": ["a", "b"]}'),
                ['notes' => ['a', 'b']],
            ],
        ];
    }
}
