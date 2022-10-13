<?php

namespace Tests\Enum;

use Enum\EnumApiValueNoFoundException;
use Enum\EnumValueNoFoundException;
use PHPUnit\Framework\TestCase;

class ExampleEnumTest extends TestCase
{
    public function directValues(): iterable
    {
        foreach (ExampleEnum::values() as $value) {
            yield "Direct $value" => [$value, $value];
        }
    }

    public function apiValues(): iterable
    {
        yield 'case insensitivity for unicode ' . ExampleEnum::HORSE => [
            ExampleEnum::HORSE,
            'PŘÍLIŠ ŽLUŤOUČKÝ KŮŇ PĚL ĎÁBELSKÉ ÓDY',
        ];

        yield 'case insensitivity ' . ExampleEnum::AAA => [ExampleEnum::AAA, 'aAa'];

        yield 'omit slashes chars ' . ExampleEnum::YES => [ExampleEnum::YES, 'Y/E/S'];
        yield 'omit non-letter chars ' . ExampleEnum::OBFUSCATE => [ExampleEnum::OBFUSCATE, 'ABCDE'];
        yield 'omit non-letter chars an case insensitivity' . ExampleEnum::OBFUSCATE => [
            ExampleEnum::OBFUSCATE,
            'abcde',
        ];
        yield 'omit spaces chars ' . ExampleEnum::AAA => [ExampleEnum::AAA, 'a a a'];
        yield 'omit dots chars ' . ExampleEnum::AAA => [ExampleEnum::AAA, 'a.a.a'];

        yield 'numeric alternative for ' . ExampleEnum::YES => [ExampleEnum::YES, 1024];
        yield 'numeric alternative for ' . ExampleEnum::AAA => [ExampleEnum::AAA, 3];
        yield 'numeric alternative for ' . ExampleEnum::OBFUSCATE => [ExampleEnum::OBFUSCATE, 0];
        yield 'numeric alternative for ' . ExampleEnum::HORSE => [ExampleEnum::HORSE, 1];

        yield 'bool alternative for ' . ExampleEnum::OBFUSCATE => [ExampleEnum::OBFUSCATE, false];

        yield 'alternative for true' => [ExampleEnum::YES, 'YES'];
        yield 'alternative for ' . ExampleEnum::OBFUSCATE . ' 1' => [ExampleEnum::OBFUSCATE, 'obf'];
        yield 'alternative for ' . ExampleEnum::OBFUSCATE . ' 2' => [ExampleEnum::OBFUSCATE, ' O B F '];
    }

    /**
     * @dataProvider directValues
     *
     * @param        $expectedResult
     * @param        $apiValue
     *
     * @throws EnumValueNoFoundException
     */
    public function testDirectValueFromAPI($expectedResult, $apiValue)
    {
        $this->assertEquals($expectedResult, ExampleEnum::valueFromAPI($apiValue));
    }

    /**
     * @dataProvider apiValues
     *
     * @param        $expectedResult
     * @param        $apiValue
     *
     * @throws EnumValueNoFoundException
     */
    public function testValueFromAPI($expectedResult, $apiValue)
    {
        $this->assertEquals($expectedResult, ExampleEnum::valueFromAPI($apiValue));
    }

    public function directApiValues(): iterable
    {
        yield 'true' => [ExampleEnum::YES, 1024];
        yield ExampleEnum::HORSE => [ExampleEnum::HORSE, 1];
        yield ExampleEnum::OBFUSCATE => [ExampleEnum::OBFUSCATE, 0];
        yield ExampleEnum::AAA => [ExampleEnum::AAA, 3];
    }

    /**
     * @dataProvider directApiValues
     *
     * @param        $expectedResult
     * @param        $value
     *
     * @throws EnumApiValueNoFoundException
     */
    public function testValueToAPI($value, $expectedResult)
    {
        $this->assertEquals($expectedResult, ExampleEnum::valueToAPI($value));
    }
}
