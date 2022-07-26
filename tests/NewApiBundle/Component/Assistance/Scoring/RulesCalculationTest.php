<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Assistance\Scoring;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringRuleOptionsEnum;
use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringRulesEnum;
use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringRuleType;
use NewApiBundle\Component\Assistance\Scoring\Model\Scoring;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringRuleOption;
use NewApiBundle\Component\Assistance\Scoring\RulesCalculation;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringRule;
use NewApiBundle\Enum\HouseholdAssets;
use NewApiBundle\Enum\HouseholdShelterStatus;
use NewApiBundle\Enum\PersonGender;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use NewApiBundle\Enum\HouseholdSupportReceivedType;

class RulesCalculationTest extends KernelTestCase
{
    /** @var RulesCalculation */
    private $rulesCalculation;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        static::bootKernel();

        $container = self::$kernel->getContainer()->get('test.service_container');

        $this->rulesCalculation = $container->get(RulesCalculation::class);
    }

    public function testCorrectMethodsFormat()
    {
        $customComputationReflection = new ReflectionClass(RulesCalculation::class);

        foreach ($customComputationReflection->getMethods() as $method) {
            if (!$method->isPublic()) {
                continue;
            }

            $this->assertEquals( 2, $method->getNumberOfParameters());

            $this->assertEquals(Household::class, $method->getParameters()[0]->getClass()->getName());
            $this->assertEquals(ScoringRule::class, $method->getParameters()[1]->getClass()->getName());

            $this->assertTrue($method->hasReturnType());
            $this->assertEquals('int', $method->getReturnType()->getName());
        }
    }

    public function testHasMethodForEverySupportedCalculation()
    {
        $supportedNotImplementedCalculations = ScoringRulesEnum::values();

        $customComputationReflection = new ReflectionClass(RulesCalculation::class);

        foreach ($customComputationReflection->getMethods() as $method) {
            if (in_array($method->getName(), $supportedNotImplementedCalculations)) {
                unset($supportedNotImplementedCalculations[array_search($method->getName(), $supportedNotImplementedCalculations)]);
            }
        }

        $this->assertEmpty($supportedNotImplementedCalculations, 'Class ' . RulesCalculation::class . ' does not contain implementation for every rule defined in ' . ScoringRulesEnum::class);
    }

    public function testEveryMethodIsDefinedInEnum()
    {
        $customComputationReflection = new ReflectionClass(RulesCalculation::class);

        foreach ($customComputationReflection->getMethods() as $method) {
            if (!$method->isPublic()) {
                continue;
            }

            $this->assertContains($method->getName(), ScoringRulesEnum::values(), 'There is implemented public method which is not in ' . ScoringRulesEnum::class . '. Class ' . RulesCalculation::class . ' should contain only methods which performs calculation of rules.');
        }
    }


    public function testDependencyRatioUkr()
    {
        $household = new Household();

        $child = new Beneficiary();
        $child->getPerson()->setDateOfBirth((new DateTime())->modify('-18 year')->modify('+1 day')); //almost 18 years

        $almostElder = new Beneficiary();
        $almostElder->getPerson()->setDateOfBirth((new DateTime())->modify('-50 year')->modify('+1 day')); //almost 50 years

        $elder = new Beneficiary();
        $elder->getPerson()->setDateOfBirth((new DateTime())->modify('-50 year')->modify('-1 day'));

        $adult = new Beneficiary();
        $adult->getPerson()->setDateOfBirth((new DateTime())->modify('-30 year'));

        $household->addBeneficiary($child);
        $household->addBeneficiary($elder);

        $household->addBeneficiary($almostElder);
        $household->addBeneficiary($adult);

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesEnum::DEPENDENCY_RATIO_UKR, 'Test');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::DEPENDENCY_RATIO_MID, 1));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::DEPENDENCY_RATIO_HIGH, 2));

        $result = $this->rulesCalculation->dependencyRatioUkr($household, $scoringRule);

        $this->assertEquals(1, $result);
    }

    public function testHhHeadGender()
    {
        $household = $this->getStandardSyrHousehold();
        $scoring = $this->getStandardSyrScoring();
        $rule = $scoring->getRuleByFieldName(ScoringRulesEnum::HH_HEAD_GENDER);
        $result = $this->rulesCalculation->hhHeadGender($household, $rule);
        $this->assertEquals(3, $result);
    }

    public function testHhHeadVulnerability()
    {
        $household = $this->getStandardSyrHousehold();
        $scoring = $this->getStandardSyrScoring();
        $rule = $scoring->getRuleByFieldName(ScoringRulesEnum::HH_HEAD_VULNERABILITY);
        $result = $this->rulesCalculation->hhHeadVulnerability($household, $rule);

        $this->assertEquals(9, $result);
    }

    public function testHhMembersVulnerability()
    {
        $household = $this->getStandardSyrHousehold();
        $scoring = $this->getStandardSyrScoring();
        $rule = $scoring->getRuleByFieldName(ScoringRulesEnum::HH_MEMBERS_VULNERABILITY);
        $result = $this->rulesCalculation->hhMembersVulnerability($household, $rule);

        $this->assertEquals(9, $result);
    }

    public function testShelterType()
    {
        $household = $this->getStandardSyrHousehold();
        $scoring = $this->getStandardSyrScoring();
        $rule = $scoring->getRuleByFieldName(ScoringRulesEnum::SHELTER_TYPE);
        $result = $this->rulesCalculation->shelterType($household, $rule);

        $this->assertEquals(4, $result);
    }

    public function testProductiveAssets()
    {
        $household = $this->getStandardSyrHousehold();
        $scoring = $this->getStandardSyrScoring();
        $rule = $scoring->getRuleByFieldName(ScoringRulesEnum::ASSETS);
        $result = $this->rulesCalculation->productiveAssets($household, $rule);

        $this->assertEquals(-6, $result);
    }

    public function testCsi()
    {
        $household = $this->getStandardSyrHousehold();
        $scoring = $this->getStandardSyrScoring();
        $rule = $scoring->getRuleByFieldName(ScoringRulesEnum::CSI);
        $result = $this->rulesCalculation->csi($household, $rule);

        $this->assertEquals(2, $result);
    }

    public function testIncomeSpentOnFood()
    {
        $household = $this->getStandardSyrHousehold();
        $scoring = $this->getStandardSyrScoring();
        $rule = $scoring->getRuleByFieldName(ScoringRulesEnum::INCOME_SPENT_ON_FOOD);
        $result = $this->rulesCalculation->incomeSpentOnFood($household, $rule);

        $this->assertEquals(3, $result);
    }

    public function testFoodConsumptionScore()
    {
        $household = $this->getStandardSyrHousehold();
        $scoring = $this->getStandardSyrScoring();
        $rule = $scoring->getRuleByFieldName(ScoringRulesEnum::FCS);
        $result = $this->rulesCalculation->fcs($household, $rule);

        $this->assertEquals(1, $result);
    }

    public function testDebt()
    {
        $household = $this->getStandardSyrHousehold();
        $scoring = $this->getStandardSyrScoring();
        $rule = $scoring->getRuleByFieldName(ScoringRulesEnum::DEBT);
        $result = $this->rulesCalculation->debt($household, $rule);

        $this->assertEquals(2, $result);
    }

    public function testAssistanceProvided()
    {
        $household = $this->getStandardSyrHousehold();
        $scoring = $this->getStandardSyrScoring();
        $rule = $scoring->getRuleByFieldName(ScoringRulesEnum::ASSISTANCE_PROVIDED);
        $result = $this->rulesCalculation->assistanceProvided($household, $rule);

        $this->assertEquals(-30, $result);
    }
    
    private function getStandardSyrScoring(): Scoring
    {
        $rules = [];

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesEnum::HH_HEAD_VULNERABILITY, 'Vulnerability Head of Household');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::CHRONICALLY_ILL, 5));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::DISABLED, 5));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::INFANT, 6));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::ELDERLY, 4));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::PREGNANT_LACTATING_FEMALE, 0));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::NO_VULNERABILITY, 0));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::OTHER, 0));
        $rules[] = $scoringRule;

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesEnum::HH_MEMBERS_VULNERABILITY, 'Vulnerability of Household Members');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::CHRONICALLY_ILL, 3));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::DISABLED, 3));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::INFANT, 2));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::ELDERLY, 2));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::PREGNANT_LACTATING_FEMALE, 2));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::NO_VULNERABILITY, 0));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::OTHER, 0));
        $rules[] = $scoringRule;

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesEnum::SHELTER_TYPE, 'Shelter type');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::SHELTER_TENT, 4));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::SHELTER_MAKESHIFT, 3));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::SHELTER_TRANSITIONAL, 3));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::SHELTER_SEVERELY_DAMAGED, 4));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::SHELTER_MODERATELY_DAMAGED, 2));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::SHELTER_NOT_DAMAGED, 0));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::SHELTER_SHARED, 2));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::SHELTER_OTHER, 0));
        $rules[] = $scoringRule;

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesEnum::ASSETS, 'Productive Assets');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::ASSETS_0_1, -2));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::ASSETS_2, -3));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::ASSETS_3, -4));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::ASSETS_4, -5));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::ASSETS_5_MORE, -6));
        $rules[] = $scoringRule;

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesEnum::CSI, 'Coping Strategy Index (CSI)');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::CSI_0_20, 0));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::CSI_20_30, 2));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::CSI_30_40, 4));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::CSI_40_MORE, 6));
        $rules[] = $scoringRule;

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesEnum::INCOME_SPENT_ON_FOOD, 'Income spent on food');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::INCOME_SPENT_0_50, 3));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::INCOME_SPENT_50_65, 4));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::INCOME_SPENT_65_75, 5));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::INCOME_SPENT_75_MORE, 6));
        $rules[] = $scoringRule;

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesEnum::FCS, 'Food consumption score');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::CONSUMPTION_POOR, 6));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::CONSUMPTION_BORDERLINE, 4));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::CONSUMPTION_ACCEPTABLE, 1));
        $rules[] = $scoringRule;

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesEnum::HH_HEAD_GENDER, 'Gender of Head of Household');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::GENDER_FEMALE, 3));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::GENDER_MALE, 1));
        $rules[] = $scoringRule;

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesEnum::DEBT, 'Debt');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::DEBT_0_5000, 1));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::DEBT_5000_20000, 2));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::DEBT_20000_60000, 3));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::DEBT_60000_100000, 4));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleOptionsEnum::DEBT_100000_MORE, 5));
        $rules[] = $scoringRule;

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesEnum::ASSISTANCE_PROVIDED, 'Assistance Provided in the Last 3 Months');
        $scoringRule->addOption(new ScoringRuleOption(HouseholdSupportReceivedType::MPCA, -3));
        $scoringRule->addOption(new ScoringRuleOption(HouseholdSupportReceivedType::CASH_FOR_WORK, -3));
        $scoringRule->addOption(new ScoringRuleOption(HouseholdSupportReceivedType::FOOD_KIT, -3));
        $scoringRule->addOption(new ScoringRuleOption(HouseholdSupportReceivedType::FOOD_VOUCHER, -3));
        $scoringRule->addOption(new ScoringRuleOption(HouseholdSupportReceivedType::HYGIENE_KIT, -3));
        $scoringRule->addOption(new ScoringRuleOption(HouseholdSupportReceivedType::SHELTER_KIT, -3));
        $scoringRule->addOption(new ScoringRuleOption(HouseholdSupportReceivedType::SHELTER_RECONSTRUCTION_SUPPORT, -3));
        $scoringRule->addOption(new ScoringRuleOption(HouseholdSupportReceivedType::NON_FOOD_ITEMS, -3));
        $scoringRule->addOption(new ScoringRuleOption(HouseholdSupportReceivedType::LIVELIHOODS_SUPPORT, -3));
        $scoringRule->addOption(new ScoringRuleOption(HouseholdSupportReceivedType::VOCATIONAL_TRAINING, -3));
        $rules[] = $scoringRule;

        return new Scoring('testSyrScoring', $rules);
    }

    private function getStandardSyrHousehold(): Household
    {
        $household = new Household();

        //head
        $beneficiary = new Beneficiary();
        $beneficiary->setHead(true);
        $beneficiary->getPerson()->setGender(PersonGender::FEMALE);
        $beneficiary->getPerson()->setDateOfBirth((new DateTime())->modify('-60 year')->modify('-1 day'));
        $vulnerabilityCriteria = new ArrayCollection();
        $vulnerabilityCriteria->add(new VulnerabilityCriterion(VulnerabilityCriterion::CRITERION_DISABLED));
        $beneficiary->setVulnerabilityCriteria($vulnerabilityCriteria);
        $household->addBeneficiary($beneficiary);

        //member
        $beneficiary = new Beneficiary();
        $beneficiary->setHead(false);
        $beneficiary->getPerson()->setGender(PersonGender::MALE);
        $beneficiary->getPerson()->setDateOfBirth((new DateTime())->modify('-16 year'));
        $vulnerabilityCriteria = new ArrayCollection();
        $vulnerabilityCriteria->add(new VulnerabilityCriterion(VulnerabilityCriterion::CRITERION_CHRONICALLY_ILL));
        $beneficiary->setVulnerabilityCriteria($vulnerabilityCriteria);
        $household->addBeneficiary($beneficiary);

        //member
        $beneficiary = new Beneficiary();
        $beneficiary->setHead(false);
        $beneficiary->getPerson()->setGender(PersonGender::FEMALE);
        $beneficiary->getPerson()->setDateOfBirth((new DateTime())->modify('-16 year'));
        $vulnerabilityCriteria = new ArrayCollection();
        $vulnerabilityCriteria->add(new VulnerabilityCriterion(VulnerabilityCriterion::CRITERION_LACTATING));
        $beneficiary->setVulnerabilityCriteria($vulnerabilityCriteria);
        $household->addBeneficiary($beneficiary);

        //shelter type
        $household->setShelterStatus(HouseholdShelterStatus::TENT);

        //assets
        $assets = [
            HouseholdAssets::AC,
            HouseholdAssets::AGRICULTURAL_LAND,
            HouseholdAssets::CAR,
            HouseholdAssets::FLATSCREEN_TV,
            HouseholdAssets::LIVESTOCK,
            HouseholdAssets::MOTORBIKE,
            HouseholdAssets::WASHING_MACHINE
        ];
        $household->setAssets($assets);

        //csi
        $household->setCopingStrategiesIndex(22);

        //income
        $household->setIncome(10000);

        //income spent on food
        $household->setIncomeSpentOnFood(4900);

        //food consumption score
        $household->setFoodConsumptionScore(36);

        //debt
        $household->setDebtLevel(5000);

        //assistance provided in the last 3 months
        $household->setSupportReceivedTypes([
            HouseholdSupportReceivedType::MPCA,
            HouseholdSupportReceivedType::CASH_FOR_WORK,
            HouseholdSupportReceivedType::FOOD_KIT,
            HouseholdSupportReceivedType::FOOD_VOUCHER,
            HouseholdSupportReceivedType::HYGIENE_KIT,
            HouseholdSupportReceivedType::SHELTER_KIT,
            HouseholdSupportReceivedType::SHELTER_RECONSTRUCTION_SUPPORT,
            HouseholdSupportReceivedType::NON_FOOD_ITEMS,
            HouseholdSupportReceivedType::LIVELIHOODS_SUPPORT,
            HouseholdSupportReceivedType::VOCATIONAL_TRAINING,
        ]);
        $household->setSupportDateReceived(new DateTime('-1 month'));

        return $household;
    }
}
