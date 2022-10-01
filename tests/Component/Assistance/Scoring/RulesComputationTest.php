<?php

declare(strict_types=1);

namespace Tests\Component\Assistance\Scoring;

use Entity\Beneficiary;
use Entity\Household;
use DateTime;
use Component\Assistance\Scoring\Enum\ScoringRuleCalculationOptionsEnum;
use Component\Assistance\Scoring\Enum\ScoringRulesCalculationsEnum;
use Component\Assistance\Scoring\Enum\ScoringRuleType;
use Component\Assistance\Scoring\Model\ScoringRuleOption;
use Component\Assistance\Scoring\RulesCalculation;
use Component\Assistance\Scoring\Model\ScoringRule;
use Component\Assistance\Scoring\RulesEnum;
use Enum\HouseholdShelterStatus;
use Enum\PersonGender;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RulesComputationTest extends KernelTestCase
{
    /** @var RulesCalculation */
    private $rulesCalculation;

    /** @var RulesEnum */
    private $rulesEnum;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        static::bootKernel();

        $container = self::$kernel->getContainer()->get('test.service_container');

        $this->rulesCalculation = $container->get(RulesCalculation::class);
        $this->rulesEnum = $container->get(RulesEnum::class);
    }

    public function testCorrectMethodsFormat()
    {
        $customComputationReflection = new ReflectionClass(RulesCalculation::class);

        foreach ($customComputationReflection->getMethods() as $method) {
            if (!$method->isPublic()) {
                continue;
            }

            $this->assertEquals(2, $method->getNumberOfParameters());

            $this->assertEquals(Household::class, $method->getParameters()[0]->getClass()->getName());
            $this->assertEquals(ScoringRule::class, $method->getParameters()[1]->getClass()->getName());

            $this->assertTrue($method->hasReturnType());
            $this->assertEquals('int', $method->getReturnType()->getName());
        }
    }

    public function testHasMethodForEverySupportedCalculation()
    {
        $supportedNotImplementedCalculations = ScoringRulesCalculationsEnum::values();

        $customComputationReflection = new ReflectionClass(RulesCalculation::class);

        foreach ($customComputationReflection->getMethods() as $method) {
            if (in_array($method->getName(), $supportedNotImplementedCalculations)) {
                unset(
                    $supportedNotImplementedCalculations[array_search(
                        $method->getName(),
                        $supportedNotImplementedCalculations
                    )]
                );
            }
        }

        $this->assertEmpty(
            $supportedNotImplementedCalculations,
            'Class ' . RulesCalculation::class . ' does not contain implementation for every rule defined in ' . ScoringRulesCalculationsEnum::class
        );
    }

    public function testEveryMethodIsDefinedInEnum()
    {
        $customComputationReflection = new ReflectionClass(RulesCalculation::class);

        foreach ($customComputationReflection->getMethods() as $method) {
            if (!$method->isPublic()) {
                continue;
            }

            $this->assertContains(
                $method->getName(),
                ScoringRulesCalculationsEnum::values(),
                'There is implemented public method which is not in ' . ScoringRulesCalculationsEnum::class . '. Class ' . RulesCalculation::class . ' should contain only methods which performs calculation of rules.'
            );
        }
    }

    public function testDependencyRatioUkr()
    {
        $child = new Beneficiary();
        $child->getPerson()->setDateOfBirth((new DateTime())->modify('-18 year')->modify('+1 day')); //almost 18 years

        $almostElder = new Beneficiary();
        $almostElder->getPerson()->setDateOfBirth(
            (new DateTime())->modify('-60 year')->modify('+1 day')
        ); //almost 60 years

        $elder = new Beneficiary();
        $elder->getPerson()->setDateOfBirth((new DateTime())->modify('-60 year')->modify('-1 day'));

        $adult = new Beneficiary();
        $adult->getPerson()->setDateOfBirth((new DateTime())->modify('-30 year'));

        $householdLow = new Household();
        $householdLow->addBeneficiary($child);
        $householdLow->addBeneficiary($almostElder);
        $householdLow->addBeneficiary($adult);

        $householdMid = new Household();
        $householdMid->addBeneficiary($child);
        $householdMid->addBeneficiary($elder);
        $householdMid->addBeneficiary($almostElder);
        $householdMid->addBeneficiary($adult);

        $householdHigh = new Household();
        $householdHigh->addBeneficiary($child);
        $householdHigh->addBeneficiary($elder);

        $scoringRule = new ScoringRule(
            ScoringRuleType::CALCULATION,
            ScoringRulesCalculationsEnum::DEPENDENCY_RATIO_UKR,
            'Test'
        );
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_MID, 1));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_HIGH, 2));

        $resultLow = $this->rulesCalculation->dependencyRatioUkr($householdLow, $scoringRule);
        $resultMid = $this->rulesCalculation->dependencyRatioUkr($householdMid, $scoringRule);
        $resultHigh = $this->rulesCalculation->dependencyRatioUkr($householdHigh, $scoringRule);

        $this->assertEquals(0, $resultLow);
        $this->assertEquals(1, $resultMid);
        $this->assertEquals(2, $resultHigh);
    }

    public function testEnumHouseholdShelterStatus()
    {
        $scoringRule = new ScoringRule('enum', 'HouseholdShelterStatus', 'Test');
        $scoringRule->addOption(new ScoringRuleOption('House/Apartment - Lightly Damaged', 1));
        $scoringRule->addOption(new ScoringRuleOption('House/Apartment - Moderately Damaged', 4));
        $scoringRule->addOption(new ScoringRuleOption('House/Apartment - Severely Damaged', 5));

        $household = new Household();

        //value defined both enum and scoring
        $household->setShelterStatus(HouseholdShelterStatus::HOUSE_APARTMENT_MODERATELY_DAMAGED);
        $result = $this->rulesEnum->getScore($household, $scoringRule);
        $this->assertEquals(4, $result);

        //value defined only in enum
        $household->setShelterStatus(HouseholdShelterStatus::HOUSE_APARTMENT_NOT_DAMAGED);
        $result = $this->rulesEnum->getScore($household, $scoringRule);
        $this->assertEquals(0, $result);
    }

    public function testDependencyRatioSyr()
    {
        $scoringRUle = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesCalculationsEnum::DEPENDENCY_RATIO_SYR, 'Dep. ratio syr');
        //todo
    }

    public function testVulnerabilityOfHeadOfHousehold()
    {
        //todo
    }

    public function testGenderOfHouseholdHead()
    {
        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesCalculationsEnum::GENDER_OF_HEAD_OF_HOUSEHOLD, 'Gender of head of household');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::GENDER_MALE, 1));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::GENDER_FEMALE, 4));

        $head = new Beneficiary();
        $head->setHead();
        $head->getPerson()->setGender(PersonGender::FEMALE);

        $household = new Household();
        $household->addBeneficiary($head);

        $score = $this->rulesCalculation->genderOfHeadOfHousehold($household, $scoringRule);
        $this->assertEquals(4, $score);

        $head->getPerson()->setGender(PersonGender::MALE);

        $score = $this->rulesCalculation->genderOfHeadOfHousehold($household, $scoringRule);
        $this->assertEquals(1, $score);
    }
}
