<?php

namespace Tests\CommonBundle\Utils;

use CommonBundle\Utils\ExportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ExportServiceTest extends TestCase
{
    /**
     * @param int   $arg
     * @param array $expectedResult
     *
     * @dataProvider providerGenerateCells
     *
     * @throws \ReflectionException
     */
    public function testGenerateCells($arg, $expectedResult)
    {
        $exportService = new ExportService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(ContainerInterface::class)
        );

        $method = new \ReflectionMethod($exportService, 'generateCells');
        $method->setAccessible(true);

        $actualResult = [];
        foreach ($method->invoke($exportService, $arg) as $item) {
            $actualResult[] = $item;
        }

        $this->assertSame($expectedResult, $actualResult);
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

