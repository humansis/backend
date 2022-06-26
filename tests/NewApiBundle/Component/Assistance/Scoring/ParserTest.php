<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Assistance\Scoring;

use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringRuleOptionsEnum;
use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringRulesEnum;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringRule;
use NewApiBundle\Component\Assistance\Scoring\Parser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ParserTest extends KernelTestCase
{
    /** @var Parser */
    private $parser;

    /** @var String */
    private $projectDir;

    public function __construct()
    {
        parent::__construct();

        $this->parser = new Parser();
        $kernel = $this->bootKernel();
        $this->projectDir = $kernel->getProjectDir();
    }

    public function testParseAllSupportedCalculation()
    {
        $csvPath = $this->projectDir .'/tests/NewApiBundle/Resources/Scoring/all_supported_calculation.csv';

        /** @var ScoringRule[] $scoringRules */
        $scoringRules = $this->parser->parse($csvPath);

        $this->assertEquals(3, count($scoringRules));

        $this->assertEquals(1, count($scoringRules[0]->getOptions()));
        $this->assertEquals(1, count($scoringRules[1]->getOptions()));
        $this->assertEquals(2, count($scoringRules[2]->getOptions()));

        foreach ($scoringRules as $rule) {
            $this->assertContains($rule->getFieldName(), ScoringRulesEnum::values());

            foreach ($rule->getOptions() as $option) {
                $this->assertContains($option->getValue(), ScoringRuleOptionsEnum::SUPPORTED[$rule->getFieldName()]);
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
}
