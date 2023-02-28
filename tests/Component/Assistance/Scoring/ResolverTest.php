<?php

declare(strict_types=1);

namespace Tests\Component\Assistance\Scoring;

use Component\Assistance\Scoring\Enum\ScoringSupportedHouseholdCoreFieldsEnum;
use Component\Assistance\Scoring\Exception\ScoreValidationException;
use Component\Assistance\Scoring\Model\Factory\ScoringFactory;
use Entity\CountrySpecific;
use Entity\CountrySpecificAnswer;
use Entity\Household;
use Doctrine\Persistence\ObjectManager;
use Component\Assistance\Scoring\Enum\ScoringRuleType;
use Component\Assistance\Scoring\Model\Scoring;
use Component\Assistance\Scoring\Model\ScoringRule;
use Component\Assistance\Scoring\Model\ScoringRuleOption;
use Component\Assistance\Scoring\ScoringResolver;
use Enum\HouseholdAssets;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ResolverTest extends KernelTestCase
{
    /** @var ScoringResolver */
    private $resolver;

    /** @var ObjectManager */
    private $objectManager;

    /** @var ScoringFactory */
    private $scoringFactory;

    public function __construct()
    {
        parent::__construct();

        $kernel = $this->bootKernel();

        $this->resolver = self::getContainer()->get(ScoringResolver::class);
        $this->objectManager = self::getContainer()->get('doctrine.orm.default_entity_manager');
        $this->scoringFactory = self::getContainer()->get(ScoringFactory::class);
    }

    public function testSimpleCountrySpecific(): void
    {
        /** @var Household $household */
        $household = $this->objectManager->getRepository(Household::class)->findOneBy([]);

        $countrySpecificName = $household->getCountrySpecificAnswers()->first()->getCountrySpecific()->getFieldString();
        $countrySpecificAnswer = $household->getCountrySpecificAnswers()->first()->getAnswer();

        $scoringRule = new ScoringRule(ScoringRuleType::COUNTRY_SPECIFIC, $countrySpecificName, 'Test Rule');
        $scoringRule->addOption(new ScoringRuleOption($countrySpecificAnswer, 5));
        $scoringRule->addOption(new ScoringRuleOption('dummy value', 1));

        $scoring = new Scoring('Test scoring', [$scoringRule]);

        $protocol = $this->resolver->compute($household, $scoring, 'KHM');

        $this->assertEquals(5, $protocol->getTotalScore());
        $this->assertEquals(5, $protocol->getScore('Test Rule'));
    }

    public function testCountrySpecificWithExpressionEvaluation()
    {
        /** @var Household $household */
        $household = $this->objectManager->getRepository(Household::class)->findOneBy([]);

        $CSO = new CountrySpecific('Test cso', 'number', 'SYR');
        $CSO->setCountryIso3('SYR');

        $countrySpecificAnswer = new CountrySpecificAnswer();
        $countrySpecificAnswer->setCountrySpecific($CSO);
        $countrySpecificAnswer->setHousehold($household);
        $countrySpecificAnswer->setAnswer('5');

        $this->objectManager->persist($CSO);
        $this->objectManager->persist($countrySpecificAnswer);
        $this->objectManager->flush();

        $scoringRule = new ScoringRule(ScoringRuleType::COUNTRY_SPECIFIC, 'Test cso', 'Test Rule');
        $scoringRule->addOption(new ScoringRuleOption('5', 1));
        $scoringRule->addOption(new ScoringRuleOption('x < 5', 2));
        $scoringRule->addOption(new ScoringRuleOption('x > 5', 3));

        $scoring = new Scoring('Test scoring', [$scoringRule]);

        $protocol = $this->resolver->compute($household, $scoring, 'SYR');
        $this->assertEquals(1, $protocol->getTotalScore());

        $countrySpecificAnswer->setAnswer('4');
        $this->objectManager->flush();

        $protocol = $this->resolver->compute($household, $scoring, 'SYR');
        $this->assertEquals(2, $protocol->getTotalScore());

        $countrySpecificAnswer->setAnswer('6');
        $this->objectManager->flush();

        $protocol = $this->resolver->compute($household, $scoring, 'SYR');
        $this->assertEquals(3, $protocol->getTotalScore());

        $this->objectManager->remove($countrySpecificAnswer);
        $this->objectManager->remove($CSO);
        $this->objectManager->flush();
    }

    public function testCoreHouseholdFail()
    {
        $this->expectException(ScoreValidationException::class);

        //non-existing field name
        $rule = new ScoringRule(ScoringRuleType::CORE_HOUSEHOLD, 'blablablablabla', 'blabla');
        $rule->addOption(new ScoringRuleOption('test', 0));

        $this->scoringFactory->createScoring('test', [$rule]);
    }

    public function testCorrectCoreHousehold()
    {
        $ruleDebtLevel = new ScoringRule(ScoringRuleType::CORE_HOUSEHOLD, ScoringSupportedHouseholdCoreFieldsEnum::DEBT_LEVEL, 'Debt level');
        $ruleDebtLevel->addOption(new ScoringRuleOption('0', 1));

        $ruleNotes = new ScoringRule(ScoringRuleType::CORE_HOUSEHOLD, ScoringSupportedHouseholdCoreFieldsEnum::NOTES, 'Notes');
        $ruleNotes->addOption(new ScoringRuleOption('test', 2));

        $scoring = $this->scoringFactory->createScoring('test', [$ruleDebtLevel, $ruleNotes]);

        $household = new Household();

        $score = $this->resolver->compute($household, $scoring, 'SYR');
        $this->assertEquals(0, $score->getTotalScore());

        $household->setDebtLevel(0);
        $household->setNotes('test');

        $score = $this->resolver->compute($household, $scoring, 'SYR');
        $this->assertEquals(3, $score->getTotalScore());
    }

    public function testHouseholdCoreArray()
    {
        $rule = new ScoringRule(ScoringRuleType::CORE_HOUSEHOLD, ScoringSupportedHouseholdCoreFieldsEnum::ASSETS, 'Assets');
        $rule->addOption(new ScoringRuleOption(HouseholdAssets::CAR, -1.5));
        $rule->addOption(new ScoringRuleOption(HouseholdAssets::AC, -1));

        $household = new Household();
        $household->setAssets([HouseholdAssets::AC, HouseholdAssets::CAR]);

        $scoring = $this->scoringFactory->createScoring('test', [$rule]);

        $score = $this->resolver->compute($household, $scoring, 'SYR');
        $this->assertEquals(-2.5, $score->getTotalScore());
    }
}
