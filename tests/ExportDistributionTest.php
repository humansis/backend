<?php

namespace Tests;

use Exception;
use Utils\ExportService;
use Entity\Assistance;
use Tests\BMSServiceTestCase;

class ExportDistributionTest extends BMSServiceTestCase
{
    public function setUp(): void
    {
        parent::setUpFunctionnal();
    }

    /**
     * Test export distribution
     * expectedDistribution.csv is a file to test the export distribution service
     *
     * @throws Exception
     */
    public function testExportDistribution()
    {
        $exportservice = new ExportService($this->em, self::getContainer());
        $exportableTable = $this->em->getRepository(Assistance::class)->findAll();

        $filename = $exportservice->export($exportableTable, 'actual', 'csv');
        $path = getcwd() . '/' . $filename;

        $this->assertEquals($filename, 'actual.csv');
        $this->assertFileExists($path);
        $this->assertFileIsReadable($path);

        unlink($path);
    }
}
