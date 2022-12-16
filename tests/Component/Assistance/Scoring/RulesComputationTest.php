<?php

declare(strict_types=1);

namespace Component\Assistance\Scoring;

use Entity\Beneficiary;
use Entity\CountrySpecific;
use Entity\CountrySpecificAnswer;
use Entity\Household;
use DateTime;
use Component\Assistance\Scoring\Enum\ScoringRuleCalculationOptionsEnum;
use Component\Assistance\Scoring\Enum\ScoringRulesCalculationsEnum;
use Component\Assistance\Scoring\Enum\ScoringRuleType;
use Component\Assistance\Scoring\Model\ScoringRuleOption;
use Component\Assistance\Scoring\Model\ScoringRule;
use Entity\VulnerabilityCriterion;
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

        $container = self::getContainer();

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
            $this->assertEquals('float', $method->getReturnType()->getName());
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

    public function testDependencyRatioSyrNWS()
    {
        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesCalculationsEnum::DEPENDENCY_RATIO_SYR_NWS, 'Dep. ratio syr');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_ZERO_DIVISION, 1));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_NWS_LOW, 2));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_NWS_HIGH, 3));

        $household = new Household();

        $score = $this->rulesCalculation->dependencyRatioSyrNWS($household, $scoringRule);
        $this->assertEquals(1, $score);

        $workingAdult = new Beneficiary();
        $workingAdult->getPerson()->setDateOfBirth((new DateTime())->modify('-30 years'));
        $household->addBeneficiary($workingAdult);

        $score = $this->rulesCalculation->dependencyRatioSyrNWS($household, $scoringRule);
        $this->assertEquals(2, $score);

        $household->addBeneficiary(clone($workingAdult));

        $child = new Beneficiary();
        $child->getPerson()->setDateOfBirth((new DateTime())->modify('-15 years'));
        $household->addBeneficiary($child);
        $household->addBeneficiary(clone($child));
        $household->addBeneficiary(clone($child));

        // 2 adults, 3 children
        $score = $this->rulesCalculation->dependencyRatioSyrNWS($household, $scoringRule);
        $this->assertEquals(2, $score);

        // 2 adults, 4 children
        $household->addBeneficiary(clone($child));
        $score = $this->rulesCalculation->dependencyRatioSyrNWS($household, $scoringRule);
        $this->assertEquals(3, $score);
    }

    public function testVulnerabilityOfHeadOfHouseholdNWS()
    {
        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesCalculationsEnum::VULNERABILITY_HEAD_OF_HOUSEHOLD_NWS, 'Vulnerability of head of household nws');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::CHRONICALLY_ILL_OR_DISABLED, 1));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::INFANT, 2));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::ELDERLY, 3));

        $head = new Beneficiary();
        $head->setHead();

        $household = new Household();
        $household->addBeneficiary($head);

        $score = $this->rulesCalculation->vulnerabilityHeadOfHouseholdNWS($household, $scoringRule);
        $this->assertEquals(0, $score);

        $head->addVulnerabilityCriterion(new VulnerabilityCriterion(VulnerabilityCriterion::CRITERION_CHRONICALLY_ILL));
        $head->addVulnerabilityCriterion(new VulnerabilityCriterion(VulnerabilityCriterion::CRITERION_DISABLED));

        $score = $this->rulesCalculation->vulnerabilityHeadOfHouseholdNWS($household, $scoringRule);
        $this->assertEquals(1, $score);

        $head->getPerson()->setDateOfBirth((new DateTime())->modify('-15 year'));

        $score = $this->rulesCalculation->vulnerabilityHeadOfHouseholdNWS($household, $scoringRule);
        $this->assertEquals(3, $score);
    }

    public function testVulnerabilityOfHeadOfHouseholdNES()
    {
        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesCalculationsEnum::VULNERABILITY_HEAD_OF_HOUSEHOLD_NES, 'Vulnerability of head of household nes');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::CHRONICALLY_ILL, 1));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::PERSON_WITH_DISABILITY, 1));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::INFANT, 1));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::ELDERLY, 1));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::PREGNANT_OR_LACTATING_FEMALE, 1));

        $head = new Beneficiary();
        $head->setHead();

        $household = new Household();
        $household->addBeneficiary($head);

        $score = $this->rulesCalculation->vulnerabilityHeadOfHouseholdNES($household, $scoringRule);
        $this->assertEquals(0, $score);

        $head->addVulnerabilityCriterion(new VulnerabilityCriterion(VulnerabilityCriterion::CRITERION_CHRONICALLY_ILL));
        $head->addVulnerabilityCriterion(new VulnerabilityCriterion(VulnerabilityCriterion::CRITERION_DISABLED));
        $head->addVulnerabilityCriterion(new VulnerabilityCriterion(VulnerabilityCriterion::CRITERION_PREGNANT));
        $head->getPerson()->setDateOfBirth((new DateTime())->modify('-15 year'));
        $head->getPerson()->setGender(PersonGender::FEMALE);

        $score = $this->rulesCalculation->vulnerabilityHeadOfHouseholdNES($household, $scoringRule);
        $this->assertEquals(4, $score);
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

    public function testIncomeSpentOnFood()
    {
        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesCalculationsEnum::INCOME_SPENT_ON_FOOD, 'Income spent on food');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::INCOME_SPENT_ON_FOOD_0, 0));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::INCOME_SPENT_ON_FOOD_MISSING_VALUE_LOW, 1));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::INCOME_SPENT_ON_FOOD_MISSING_VALUE_HIGH, 2));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::INCOME_SPENT_ON_FOOD_INF, 99));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::INCOME_SPENT_ON_FOOD_95, 95));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::INCOME_SPENT_ON_FOOD_80, 80));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::INCOME_SPENT_ON_FOOD_65, 65));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::INCOME_SPENT_ON_FOOD_50, 50));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::INCOME_SPENT_ON_FOOD_25, 25));

        $cso = new CountrySpecific('Total expenditure', 'number', 'SYR');

        $csa = new CountrySpecificAnswer();
        $csa->setAnswer('1');
        $csa->setCountrySpecific($cso);

        $household = new Household();
        $household->setIncome(2);
        $household->addCountrySpecificAnswer($csa);

        $score = $this->rulesCalculation->incomeSpentOnFood($household, $scoringRule);
        $this->assertEquals(50, $score);

        $household->setIncome(0);
        $score = $this->rulesCalculation->incomeSpentOnFood($household, $scoringRule);
        $this->assertEquals(2, $score);

        $household->setIncome(99);
        $score = $this->rulesCalculation->incomeSpentOnFood($household, $scoringRule);
        $this->assertEquals(25, $score);

        $household->setIncome(100);
        $csa->setAnswer('51');
        $score = $this->rulesCalculation->incomeSpentOnFood($household, $scoringRule);
        $this->assertEquals(65, $score);

        $household->setIncome(null);
        $score = $this->rulesCalculation->incomeSpentOnFood($household, $scoringRule);
        $this->assertEquals(2, $score);
    }

    public function testVulnerabilityOfHouseholdMembers()
    {
        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesCalculationsEnum::VULNERABILITY_OF_HOUSEHOLD_MEMBERS, 'Vulnerability of household members');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::VULNERABILITY_HHM_ILL, 2));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::VULNERABILITY_HHM_NO_ILL, 1));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::VULNERABILITY_HHM_PREGNANT, 2));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::VULNERABILITY_HHM_NO_PREGNANT, 1));

        $household = new Household();

        $head = new Beneficiary();
        $head->setHead();
        $head->addVulnerabilityCriterion(new VulnerabilityCriterion(VulnerabilityCriterion::CRITERION_PREGNANT));
        $head->getPerson()->setGender(PersonGender::FEMALE);

        $household->addBeneficiary($head);

        $score = $this->rulesCalculation->vulnerabilityOfHouseholdMembers($household, $scoringRule);
        $this->assertEquals(2, $score);

        $member = new Beneficiary();
        $member->setHead(false);
        $member->addVulnerabilityCriterion(new VulnerabilityCriterion(VulnerabilityCriterion::CRITERION_DISABLED));
        $member->addVulnerabilityCriterion(new VulnerabilityCriterion(VulnerabilityCriterion::CRITERION_LACTATING));
        $member->getPerson()->setGender(PersonGender::FEMALE);

        $household->addBeneficiary($member);

        $score = $this->rulesCalculation->vulnerabilityOfHouseholdMembers($household, $scoringRule);
        $this->assertEquals(4, $score);
    }

    public function testDependencyRatioSyrNES()
    {
        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesCalculationsEnum::DEPENDENCY_RATIO_SYR_NES, 'Dep. ratio syr nes');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_NES_0, 0));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_NES_1, 1));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_NES_2, 2));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_NES_3, 3));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_NES_4, 4));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_NES_5, 5));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_NES_INF, 99));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_ZERO_DIVISION, 10));

        $household = new Household();

        $child = new Beneficiary();
        $child->getPerson()->setDateOfBirth((new DateTime())->modify('-10 years'));
        $household->addBeneficiary($child);

        $score = $this->rulesCalculation->dependencyRatioSyrNES($household, $scoringRule);
        $this->assertEquals(10, $score);

        $adult = new Beneficiary();
        $adult->getPerson()->setDateOfBirth((new DateTime())->modify('-30 years'));
        $household->addBeneficiary($adult);

        $score = $this->rulesCalculation->dependencyRatioSyrNES($household, $scoringRule);
        $this->assertEquals(1, $score);

        $household->addBeneficiary(clone($child));
        $household->addBeneficiary(clone($child));
        $household->addBeneficiary(clone($child));

        $score = $this->rulesCalculation->dependencyRatioSyrNES($household, $scoringRule);
        $this->assertEquals(4, $score);

        $household->addBeneficiary(clone($child));
        $household->addBeneficiary(clone($child));
        $household->addBeneficiary(clone($child));

        $score = $this->rulesCalculation->dependencyRatioSyrNES($household, $scoringRule);
        $this->assertEquals(99, $score);
    }

    public function testVulnerabilityCriterion()
    {
        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesCalculationsEnum::VULNERABILITY_CRITERION, 'Vulnerability criterion');
        $scoringRule->addOption(new ScoringRuleOption(VulnerabilityCriterion::CRITERION_CHRONICALLY_ILL, 1));
        $scoringRule->addOption(new ScoringRuleOption(VulnerabilityCriterion::CRITERION_DISABLED, 2));
        $scoringRule->addOption(new ScoringRuleOption(VulnerabilityCriterion::CRITERION_PREGNANT, 3));
        $scoringRule->addOption(new ScoringRuleOption(VulnerabilityCriterion::CRITERION_LACTATING, 4));
        $scoringRule->addOption(new ScoringRuleOption(VulnerabilityCriterion::CRITERION_NUTRITIONAL_ISSUES, 5));
        $scoringRule->addOption(new ScoringRuleOption(VulnerabilityCriterion::CRITERION_SOLO_PARENT, 6));

        $household = new Household();

        $member = new Beneficiary();
        $member->addVulnerabilityCriterion(new VulnerabilityCriterion(VulnerabilityCriterion::CRITERION_CHRONICALLY_ILL));
        $household->addBeneficiary($member);

        $score = $this->rulesCalculation->vulnerabilityCriterion($household, $scoringRule);
        $this->assertEquals(1, $score);
        $household->addBeneficiary(clone($member));

        $score = $this->rulesCalculation->vulnerabilityCriterion($household, $scoringRule);
        $this->assertEquals(1, $score);

        $member2 = new Beneficiary();
        $member2->addVulnerabilityCriterion(new VulnerabilityCriterion(VulnerabilityCriterion::CRITERION_DISABLED));
        $household->addBeneficiary($member2);

        $score = $this->rulesCalculation->vulnerabilityCriterion($household, $scoringRule);
        $this->assertEquals(3, $score);

        $member2->addVulnerabilityCriterion(new VulnerabilityCriterion(VulnerabilityCriterion::CRITERION_SOLO_PARENT));

        $score = $this->rulesCalculation->vulnerabilityCriterion($household, $scoringRule);
        $this->assertEquals(9, $score);
    }
}
