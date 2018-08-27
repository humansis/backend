<?php

namespace App\tests\CommonBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use CommonBundle\Utils\ExportService;
use DistributionBundle\Entity\DistributionData;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use \PHPUnit\Framework\TestCase;
use ReportingBundle\Entity\ReportingIndicator;
use ReportingBundle\Utils\Formatters\Formatter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Tests\BMSServiceTestCase;
use ReportingBundle\Utils\Computers\Computer;


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

    public function testExportDistribution () {

        $exportservice = new ExportService($this->em,$this->container);

        $exportableTable = $this->em->getRepository(DistributionData::class)->findAll();

        $csv = $exportservice->export($exportableTable,'actual');

        $this->assertEquals(file_get_contents('expectedDistribution.csv'), $csv['content']);


    }

    public function testExportReports() {
        

        $filters = {

            "filters":

            {
                "__country" : ["TH"],
                "frequency": ["YEAR"]

            }

               };


        $header = [
            ["Frequency", 'Year'],
            ["Country", 'TH'],
            ["Indicator name", 'BMS_Country_TH'],
            ["Indicator Reference", 'Total Households'],
            ["Graph type", 'line'],
        ];

        $exportservice = new ExportService($this->em,$this->container);

        $indicator = $this->em->getRepository(ReportingIndicator::class)->find(1);

        $computer = new Computer();

        $format = new Formatter();



        $datacomputed = $computer->compute($indicator,$filters);
        $dataFormatted = $format->format(Formatter::CsvFormat,$datacomputed,'line');

        $csv = $exportservice->setHeaders($header)->export($dataFormatted,'actual');

        $this->assertEquals(file_get_contents('expectedReporting.csv'), $csv['content']);

    }





}
