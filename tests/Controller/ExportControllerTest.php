<?php

namespace Tests\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Entity\Project;
use Exception;
use Tests\BMSServiceTestCase;
use Entity\UserProject;

class ExportControllerTest extends BMSServiceTestCase
{
    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("serializer");
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::getContainer()->get('test.client');
    }

    public function availableExportProvider(): array
    {
        $allTypes = ['csv', 'pdf', 'xlsx', 'ods'];
        $exceptPdf = ['csv', 'xlsx', 'ods'];
        $emptyFilter = [
            'filter' => [],
            'sort' => [''],
            'pageIndex' => 0,
            'pageSize' => -1,
            'sort' => ['sort' => null, 'direction' => null],
        ];
        $availableExports = [
            'Assistance' => [$allTypes, 'distributions=2'],
            'Assistance2' => [$allTypes, 'officialDistributions=2'],
            'Beneficiaries' => [$exceptPdf, 'beneficiaries=true', ['ids' => [2, 3]]],
            'Beneficiaries in Distribution' => [$allTypes, 'beneficiariesInDistribution=1'],
            'Users' => [$exceptPdf, 'users=true'],
            'Countries' => [$exceptPdf, 'countries=true'],
            'Donors' => [$exceptPdf, 'donors=true'],
            'Project in country' => [$exceptPdf, 'projects=ARM'],
            'Booklets' => [$allTypes, 'bookletCodes=true', ['ids' => [1, 2, 3, 4]]],
            // 'General assistance' => [$allTypes, 'generalreliefDistribution=2'], TODO: fix Fixtures to make testable assistance
            'Voucher assistance' => [$allTypes, 'voucherDistribution=4'],
            'Transaction assistance' => [$allTypes, 'transactionDistribution=1'],
            'Smartcard assistance' => [$allTypes, 'smartcardDistribution=17'],
            'Products' => [$exceptPdf, 'products=true'],
            'Vendors' => [$exceptPdf, 'vendors=true'],
        ];
        $expandedTypes = [];
        foreach ($availableExports as $name => $export) {
            foreach ($export[0] as $type) {
                $expandedTypes[$name . " into " . $type] = [$type, $export[1], $export[2] ?? []];
            }
        }

        return $expandedTypes;
    }

    /**
     * @dataProvider availableExportProvider
     *
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testExport(string $type, string $otherQuery, array $body = []): void
    {
        $this->markTestSkipped('Export tests takes too much time. It kills processing.');

        $url = "/api/basic/export?type=$type&" . $otherQuery;
        $crawler = $this->request('POST', $url, $body);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            "Request $url failed: " . $this->client->getResponse()->getContent()
        );
    }
}
