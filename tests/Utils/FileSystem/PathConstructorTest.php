<?php
declare(strict_types=1);

namespace Tests\Utils\FileSystem;


use InvalidArgumentException;
use Utils\FileSystem\PathConstructor;
use PHPUnit\Framework\TestCase;

class PathConstructorTest extends TestCase
{
    public function constructDataProvider(): array
    {
        return [
            [
                '/test/path',
                [],
                '/test/path',
            ],
            [
                '/test/path',
                ['param' => 'test'],
                '/test/path',
            ],
            [
                '/test/path/<<param>>/',
                ['param' => 'test-replacement'],
                '/test/path/test-replacement/',
            ],
            [
                '/test/path/<<param1>>/path/to/<<param2>>/file',
                ['param1' => 'test-replacement', 'param2' => 'test-replacement2'],
                '/test/path/test-replacement/path/to/test-replacement2/file',
            ],
        ];
    }

    /**
     * @dataProvider constructDataProvider
     *
     * @param string $template
     * @param array  $params
     * @param string $expectedResultPath
     */
    public function testConstruct(string $template, array $params, string $expectedResultPath)
    {
        $resultPath = PathConstructor::construct($template, $params);

        $this->assertEquals($expectedResultPath, $resultPath);
    }

    public function testConstructFail()
    {
        $this->expectException(InvalidArgumentException::class);

        PathConstructor::construct('/<<param1>>/<<param2>>', ['param1' => 'test']);
    }
}


