<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Assistance\Scoring;

use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringRuleCalculationOptionsEnum;
use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringRulesCalculationsEnum;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringRule;
use NewApiBundle\Component\Assistance\Scoring\ScoringCsvParser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ParserTest extends KernelTestCase
{
    /** @var ScoringCsvParser */
    private $parser;

    /** @var String */
    private $projectDir;

    public function __construct()
    {
        parent::__construct();

        $this->parser = new ScoringCsvParser();
        $kernel = $this->bootKernel();
        $this->projectDir = $kernel->getProjectDir();
    }

    public function testParseAllSupportedCalculation()
    {
        $csvPath = $this->projectDir .'/tests/NewApiBundle/Resources/Scoring/all_supported_calculation.csv';

        /** @var ScoringRule[] $scoringRules */
        $scoringRules = $this->parser->parse($csvPath);

        $this->assertEquals(count(ScoringRulesCalculationsEnum::values()), count($scoringRules));

        $this->assertEquals(1, count($scoringRules[0]->getOptions()));
        $this->assertEquals(1, count($scoringRules[1]->getOptions()));
        $this->assertEquals(2, count($scoringRules[2]->getOptions()));
        $this->assertEquals(2, count($scoringRules[3]->getOptions()));
        $this->assertEquals(7, count($scoringRules[4]->getOptions()));
        $this->assertEquals(7, count($scoringRules[5]->getOptions()));
        $this->assertEquals(6, count($scoringRules[6]->getOptions()));
        $this->assertEquals(5, count($scoringRules[7]->getOptions()));
        $this->assertEquals(4, count($scoringRules[8]->getOptions()));
        $this->assertEquals(4, count($scoringRules[9]->getOptions()));
        $this->assertEquals(3, count($scoringRules[10]->getOptions()));
        $this->assertEquals(2, count($scoringRules[11]->getOptions()));
        $this->assertEquals(5, count($scoringRules[12]->getOptions()));
        $this->assertEquals(10, count($scoringRules[13]->getOptions()));

        foreach ($scoringRules as $rule) {
            $this->assertContains($rule->getFieldName(), ScoringRulesCalculationsEnum::values());

            foreach ($rule->getOptions() as $option) {
                $this->assertContains($option->getValue(), ScoringRuleCalculationOptionsEnum::SUPPORTED[$rule->getFieldName()]);
            }
        }
    }

    public function testParseCountrySpecific()
    {
        $csvPath = $this->projectDir .'/tests/NewApiBundle/Resources/Scoring/single_country_specific.csv';

        /** @var ScoringRule[] $scoringRules */
        $scoringRules = $this->parser->parse($csvPath);

        $this->assertEquals(1, count($scoringRules));
    }

    public function testParseAllSupportedEnums()
    {
        $csvPath = $this->projectDir .'/tests/NewApiBundle/Resources/Scoring/all_supported_enums.csv';

        /** @var ScoringRule[] $scoringRules */
        $scoringRules = $this->parser->parse($csvPath);

        $this->assertEquals(1, count($scoringRules));
    }
}
