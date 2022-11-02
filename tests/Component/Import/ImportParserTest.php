<?php

declare(strict_types=1);

namespace Tests\Component\Import;

use Component\Import\ImportParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

class ImportParserTest extends TestCase
{
    private static \Symfony\Component\HttpFoundation\File\File $file;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$file = new File(__DIR__ . '/../../Resources/KHM-Import-2HH-3HHM-24HHM.ods');
    }

    public function testParse()
    {
        $parser = new ImportParser();
        $list = $parser->parse(self::$file);

        $this->assertCount(2, $list, 'Expected number of Households');
        $this->assertCount(1, $list[0], 'Expected number of members in 1. HH is one.');
        $this->assertCount(4, $list[1], 'Expected number of members in 2. HH is four.');
    }
}
