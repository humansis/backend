<?php

namespace App\tests\CommonBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Utils\ExportService;
use Tests\BMSServiceTestCase;



class ExportServiceTest extends BMSServiceTestCase {



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

        $csv = $exportservice->export($exportableTable,'actual');

        $this->assertEquals(file_get_contents('expectedExport.csv'), $csv['content']);


    }
}
