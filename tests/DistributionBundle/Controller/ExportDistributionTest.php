<?php

namespace App\tests\CommonBundle\Controller;

use CommonBundle\Utils\ExportService;
use DistributionBundle\Entity\DistributionData;
use Tests\BMSServiceTestCase;



class ExportDistributionTest extends BMSServiceTestCase {



    public function setUp()
    {
        parent::setUpFunctionnal();

    }

    /**
     * TODO
     * to test export distribution
     * expectedDistribution.csv is a file to test the export distribution service
     * @throws \Exception
     */


    public function testExportDistribution () {

        $exportservice = new ExportService($this->em,$this->container);

        $exportableTable = $this->em->getRepository(DistributionData::class)->findAll();

        $csv = $exportservice->export($exportableTable,'actual');

        $this->assertEquals(file_get_contents('expectedDistribution.csv'), $csv['content']);


    }





}
