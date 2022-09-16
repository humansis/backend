<?php

namespace Tests\Utils;

use Utils\ExcelColumnsGenerator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ExcelColumnsGeneratorTest extends KernelTestCase
{
    /**
     * @param int   $arg            number of iterations
     * @param array $expectedResult
     *
     * @dataProvider providerGenerateCells
     */
    public function testGenerateCells($arg, $expectedResult)
    {
        $generator = new ExcelColumnsGenerator();

        $actualResult = [];
        for ($i = 0; $i < $arg; ++$i) {
            $actualResult[] = $generator->getNext();
        }

        $this->assertSame($expectedResult, $actualResult);
    }

    public function testReset()
    {
        $generator = new ExcelColumnsGenerator();

        $actualResult = [];
        $actualResult[] = $generator->getNext();
        $actualResult[] = $generator->getNext();
        $actualResult[] = $generator->getNext();

        $generator->reset();

        $actualResult[] = $generator->getNext();
        $actualResult[] = $generator->getNext();

        $this->assertSame(['A', 'B', 'C', 'A', 'B'], $actualResult);
    }

    public function providerGenerateCells()
    {
        return [
            'A - E' => [5, ['A', 'B', 'C', 'D', 'E']],
            'A - AD' => [30, ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD']],
            'A - BF' => [58, ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ', 'BA', 'BB', 'BC', 'BD', 'BE', 'BF']],
        ];
    }
}
