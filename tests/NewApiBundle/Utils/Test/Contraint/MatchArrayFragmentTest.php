<?php

declare(strict_types=1);

namespace Tests\NewApiBundle\Utils\Test\Contraint;

use NewApiBundle\Utils\Test\Contraint\MatchArrayFragment;
use PHPUnit\Framework\TestCase;

class MatchArrayFragmentTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testMatches(bool $result, $expected, $actual)
    {
        $object = new MatchArrayFragment($expected);
        $res = $object->evaluate($actual, '', true);

        $result ? $this->assertTrue($res) : $this->assertFalse($res);
    }

    public function dataProvider()
    {
        return [
            // expected result, array to match, array to by matched
            [
                false, [1], ['a' => 1],
            ], [
                false, [1], ['a' => [1]],
            ], [
                false, [1], [[1]],
            ], [
                false, 1, [1],
            ], [
                false, [1], 1,
            ], [
                false, [1], ['1'],
            ], [
                false, ['a' => '*'], ['b' => ['a' => 'b']],
            ], [
                false, ['a' => 5, 'b' => [['c' => '*', 'd' => '*']]], ['a' => 5, 'b' => [0 => ['c' => 3], 1 => ['c' => 4]]],
            ], [
                true, [1], [1, 2],
            ], [
                true, ['a' => '*'], ['b' => 3, 'a' => 'b'],
            ], [
                true, ['a' => 'b'], ['b' => 3, 'a' => 'b'],
            ], [
                true, ['a' => ['b' => 'c']], ['a' => ['b' => 'c', 'd' => 'e']],
            ], [
                true, ['a' => ['b' => '*']], ['a' => ['b' => ['c', 'd' => 'e']]],
            ], [
                true, ['a' => 5, 'b' => [['c' => '*']]], ['a' => 5, 'b' => [0 => ['c' => 3, 'd' => 3], 1 => ['c' => 4, 'd' => 4]]],
            ],
        ];
    }
}
