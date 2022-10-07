<?php

declare(strict_types=1);

namespace Tests\Request;

use InvalidArgumentException;
use Request\Pagination;
use PHPUnit\Framework\TestCase;

class PaginationTest extends TestCase
{
    public function testFirstPaginationArgumentShouldBeGreaterThanZero()
    {
        $this->expectException(InvalidArgumentException::class);

        new Pagination(0, 0);
    }

    public function testSecondPaginationArgumentShouldBeGreaterThanZero()
    {
        $this->expectException(InvalidArgumentException::class);

        new Pagination(1, 0);
    }

    /**
     * @dataProvider offsetCalculationProvider
     */
    public function testOffsetCalculation($expected, $page, $size)
    {
        $actual = (new Pagination($page, $size))->getOffset();

        $this->assertSame(
            $expected,
            $actual,
            sprintf('Offset should be %d for page %d and size %d', $expected, $page, $size)
        );
    }

    public function offsetCalculationProvider()
    {
        return [
            // offset, page, size
            [0, 1, 20],
            [6, 3, 3],
            [40, 3, 20],
        ];
    }
}
