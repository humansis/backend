<?php

namespace Tests\BeneficiaryBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Utils\ExportService;
use Tests\BMSServiceTestCase;



class ExportBeneficiaryTest extends BMSServiceTestCase {



    public function setUp()
    {
        parent::setUpFunctionnal();

    }

    /**
     * @dataProvider
     * @throws \Exception
     */
    public function testExport() {

        $exportservice = new ExportService($this->em,$this->container);
        $exportableTable = $this->em->getRepository(Beneficiary::class)->findAll();

        $filename = $exportservice->export($exportableTable, 'actual', 'csv');
        $path = getcwd() . '/' . $filename;

        $this->assertEquals($filename, 'actual.csv');
        $this->assertFileExists($path);
        $this->assertFileIsReadable($path);

        unlink($path);
    }
}
