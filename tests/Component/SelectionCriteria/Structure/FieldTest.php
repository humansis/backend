<?php
declare(strict_types=1);

namespace Tests\Component\SelectionCriteria\Structure;

use Component\SelectionCriteria\Structure\Field;
use PHPUnit\Framework\TestCase;

class FieldTest extends TestCase
{
    public function testConditionsShouldNotBeEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Field('field', 'Text label', [], 'type');
    }

    /**
     * @param $type
     *
     * @dataProvider typeProvider
     */
    public function testTypeShouldBeValid($type)
    {
        try {
            new Field('field', 'Text label', [1], $type);

            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail($type.' is not valid type');
        }
    }

    /**
     * @param $callback
     *
     * @dataProvider callbackProvider
     */
    public function testCallbackShouldBeValid($callback)
    {
        try {
            new Field('field', 'Text label', [1], 'integer', $callback);

            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Invalid callback');
        }
    }

    public static function typeProvider()
    {
        return [
            ['string'],
            ['bool'],
            ['boolean'],
            ['integer'],
        ];
    }

    public static function callbackProvider()
    {
        return [
            ['is_bool'],
            [[self::class, 'callbackProvider']],
            [null],
        ];
    }
}
