<?php

namespace Tests\Controller;

use Entity\Beneficiary;
use Exception;
use Utils\ExportService;
use Tests\BMSServiceTestCase;

/**
 * @deprecated This does not belong to Controller, for sure
 */
class ExportBeneficiaryTest extends BMSServiceTestCase
{
    public function setUp(): void
    {
        parent::setUpFunctionnal();
    }

    /**
     * @dataProvider
     * @throws Exception
     */
    public function testExport()
    {
        $array = [];
        $exportservice = new ExportService($this->em, self::getContainer());
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
