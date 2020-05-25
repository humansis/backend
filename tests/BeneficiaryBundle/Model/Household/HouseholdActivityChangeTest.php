<?php

namespace Tests\BeneficiaryBundle\Model\Household;

use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\HouseholdActivity;
use BeneficiaryBundle\Model\Household\Exception\NoChangesException;
use BeneficiaryBundle\Model\Household\HouseholdActivityChange;
use PHPUnit\Framework\TestCase;
use UserBundle\Entity\User;

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
        $object = new HouseholdActivityChange($new, $old);

        $this->assertEquals($expectedChanges, $object->getChanges());
    }

    public function testNoChangesShouldThrownException()
    {
        $household = new Household();
        $author = new User();

        $old = new HouseholdActivity($household, $author, '{"livelihood": 0, "notes": "aaa"}');
        $new = new HouseholdActivity($household, $author, '{"livelihood": 0, "notes": "aaa"}');

        $this->expectException(NoChangesException::class);

        $object = new HouseholdActivityChange($new, $old);
        $object->getChanges();
    }

    public function providerChanges()
    {
        $household = new Household();
        $author = new User();

        return [
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
