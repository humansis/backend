<?php

namespace Tests\BeneficiaryBundle\Controller;

use NewApiBundle\Entity\Beneficiary;
use CommonBundle\Utils\ExportService;
use Tests\BMSServiceTestCase;

class ExportBeneficiaryTest extends BMSServiceTestCase
{
    public function setUp()
    {
        parent::setUpFunctionnal();
    }

    /**
     * @dataProvider
     * @throws \Exception
     */
    public function testExport()
    {
        $exportservice = new ExportService($this->em, self::$container);
        $exportableTable = $this->em->getRepository(Beneficiary::class)->findOneBy([], ['id' => 'asc']);

        $array[0] = $exportableTable;
        $filename = $exportservice->export($array, 'actual', 'csv');
        $path = getcwd() . '/' . $filename;

        $this->assertEquals($filename, 'actual.csv');
        $this->assertFileExists($path);
        $this->assertFileIsReadable($path);

        unlink($path);
    }
}
