<?php

declare(strict_types=1);

namespace Tests\Component\Assistance\Scoring;

use Component\Assistance\Scoring\Enum\ScoringRuleCalculationOptionsEnum;
use Component\Assistance\Scoring\Enum\ScoringRulesCalculationsEnum;
use Component\Assistance\Scoring\Model\Factory\ScoringFactory;
use Component\Assistance\Scoring\Model\ScoringRule;
use Component\Assistance\Scoring\ScoringCsvParser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ParserTest extends KernelTestCase
{
    /** @var ScoringCsvParser */
    private $parser;

    /** @var String */
    private $projectDir;

    /** @var ScoringFactory */
    private $scoringFactory;

    public function __construct()
    {
        parent::__construct();

        $this->parser = new ScoringCsvParser();
        $kernel = $this->bootKernel();
        $this->projectDir = $kernel->getProjectDir();
        $this->scoringFactory = $kernel->getContainer()->get(ScoringFactory::class);
    }

    public function testParseAllSupportedCalculation()
    {
        $csvPath = $this->projectDir . '/tests/Resources/Scoring/all_supported_calculation.csv';

        /** @var ScoringRule[] $scoringRules */
        $scoringRules = $this->parser->parse($csvPath);

        $this->assertEquals(count(ScoringRulesCalculationsEnum::values()), count($scoringRules));

        $this->assertEquals(1, count($scoringRules[0]->getOptions()));
        $this->assertEquals(1, count($scoringRules[1]->getOptions()));
        $this->assertEquals(2, count($scoringRules[2]->getOptions()));

        foreach ($scoringRules as $rule) {
            $this->assertContains($rule->getFieldName(), ScoringRulesCalculationsEnum::values());

            foreach ($rule->getOptions() as $option) {
                $this->assertContains(
                    $option->getValue(),
                    ScoringRuleCalculationOptionsEnum::SUPPORTED[$rule->getFieldName()]
                );
            }
        }
    }

    public function testParseCountrySpecific()
    {
        $csvPath = $this->projectDir . '/tests/Resources/Scoring/single_country_specific.csv';

        /** @var ScoringRule[] $scoringRules */
        $scoringRules = $this->parser->parse($csvPath);

        $this->assertEquals(1, count($scoringRules));
    }

    public function testParseAllSupportedScorings()
    {
        $directory = $this->projectDir . '/src/Resources/Scoring/*';

        $files = glob($directory);

        foreach ($files as $file) {
            $rules = $this->parser->parse($file);

            $this->scoringFactory->createScoring('test scoring', $rules);
        }

        $this->assertTrue(true);
    }
}
