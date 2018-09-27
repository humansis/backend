<?php

namespace App\tests\DistributionBundle\Controller;

use CommonBundle\Utils\ExportService;
use DistributionBundle\Entity\DistributionData;
use Tests\BMSServiceTestCase;



class ExportDistributionTest extends BMSServiceTestCase {



    public function setUp()
    {
        parent::setUpFunctionnal();

    }

    /**
     * Test export distribution
     * expectedDistribution.csv is a file to test the export distribution service
     * @throws \Exception
     */
    public function testExportDistribution () {

        $exportservice = new ExportService($this->em,$this->container);
        $exportableTable = $this->em->getRepository(DistributionData::class)->findAll();

        $filename = $exportservice->export($exportableTable, 'actual', 'csv');
        $path = getcwd() . '/' . $filename;

        $this->assertEquals($filename, 'actual.csv');
        $this->assertFileExists($path);
        $this->assertFileIsReadable($path);

        unlink($path);
    }





}
