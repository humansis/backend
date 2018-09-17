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

        $csv = $exportservice->export($exportableTable,'actual', 'csv');

        $getResourceBeneficiary = fgets(fopen(__DIR__ . '/../Resources/expectedBeneficiary.csv', 'r'));
        $getContentBeneficiary = strtok($csv['content'], "\n");

        $this->assertEquals($getResourceBeneficiary, $getContentBeneficiary);

    }
}
