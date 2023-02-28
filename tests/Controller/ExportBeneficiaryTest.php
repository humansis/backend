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
    /** @var BeneficiaryTransformData $beneficiaryTransformData */
    protected $beneficiaryTransformData;

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
        $this->beneficiaryTransformData = $this->getContainer()->get('Utils\BeneficiaryTransformData');
        $beneficiaries = $this->em->getRepository(Beneficiary::class)->findBy([], ['id' => 'asc'], 10);
        $exportableTable = $this->beneficiaryTransformData->transformData($beneficiaries, $this->iso3);
        $response = $exportTableService->export($exportableTable, 'beneficiaryhousehoulds', 'xlsx');


        $this->assertEquals(
            $response->headers->get('Content-Disposition'),
            'attachment; filename=beneficiaryhousehoulds.xlsx'
        );
    }
}
