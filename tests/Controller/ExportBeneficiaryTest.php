<?php

namespace Tests\Controller;

use Entity\Beneficiary;
use Exception;
use Utils\BeneficiaryTransformData;
use Tests\BMSServiceTestCase;
use Utils\OpenSpoutExportService;

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
        $exportTableService = new OpenSpoutExportService();
        $beneficiaryTransformData = new BeneficiaryTransformData();
        $beneficiaries = $this->em->getRepository(Beneficiary::class)->findBy([], ['id' => 'asc'], 10);
        $exportableTable = $beneficiaryTransformData->transformData($beneficiaries);
        $response = $exportTableService->export($exportableTable, 'beneficiaryhousehoulds', 'xlsx');


        $this->assertEquals(
            $response->headers->get('Content-Disposition'),
            'attachment; filename=beneficiaryhousehoulds.xlsx'
        );
    }
}
