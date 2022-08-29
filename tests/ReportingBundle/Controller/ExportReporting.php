<?php

namespace App\tests\ReportingBundle\Controller;

use CommonBundle\Utils\ExportService;
use ReportingBundle\Entity\ReportingIndicator;
use ReportingBundle\Utils\Formatters\Formatter;
use Tests\BMSServiceTestCase;
use ReportingBundle\Utils\Computers\Computer;

class ExportReporting extends BMSServiceTestCase
{
    public function setUp(): void
    {
        parent::setUpFunctionnal();
    }

    /**
     * TODO add buttons to export every graph
     * TODO add file to compare the actual and the expected in order to run the test
     * @throws \Exception
     */
    public function ExportReports()
    {
        $filters = [
            "filters" => [
                "__country" => ["TH"],
                "frequency"=> ["YEAR"]
            ]
        ];

        $header = [
            ["Frequency", 'Year'],
            ["Country", 'TH'],
            ["Indicator name", 'BMS_Country_TH'],
            ["Indicator Reference", 'Total Households'],
            ["Graph type", 'line'],
        ];

        $exportservice = new ExportService($this->em, self::$container);

        $indicator = $this->em->getRepository(ReportingIndicator::class)->find(1);

        $computer = new Computer();

        $format = new Formatter();

        $datacomputed = $computer->compute($indicator, $filters);
        $dataFormatted = $format->format(Formatter::CsvFormat, $datacomputed, 'line');

        $csv = $exportservice->export($dataFormatted, 'actual');

        $this->assertEquals(file_get_contents('expectedReporting.csv'), $csv['content']);
    }
}
