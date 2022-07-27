<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Assistance\Scoring;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use DateTime;
use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringRuleCalculationOptionsEnum;
use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringRulesCalculationsEnum;
use Doctrine\Common\Collections\ArrayCollection;
use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringRuleType;
use NewApiBundle\Component\Assistance\Scoring\Model\Scoring;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringRuleOption;
use NewApiBundle\Component\Assistance\Scoring\RulesCalculation;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringRule;
use NewApiBundle\Component\Assistance\Scoring\RulesEnum;
use NewApiBundle\Enum\HouseholdAssets;
use NewApiBundle\Enum\HouseholdShelterStatus;
use NewApiBundle\Enum\PersonGender;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use NewApiBundle\Enum\HouseholdSupportReceivedType;

class RulesCalculationTest extends KernelTestCase
{
    const HH_SUPPORT_RECEIVED_TYPES = 'supportReceivedTypes';
    const HH_SUPPORT_DATE_RECEIVED = 'supportDateReceived';
    const HH_DEBT_LEVEL = 'debtLevel';
    const HH_FCS = 'foodConsumptionScore';
    const HH_ISF = 'incomeSpentOnFood';
    const HH_INCOME = 'income';
    const HH_CSI = 'copingStrategyIndex';
    const HH_ASSETS = 'assets';
    const HH_SHELTER_STATUS = 'shelterStatus';
    const HH_MEMBERS = 'member';
    const HH_MEMBER_HEAD = 'hhMemberHead';
    const HH_MEMBER_GENDER = 'hhMemberGender';
    const HH_MEMBER_BIRTH_DATE = 'hhMemberBirthDate';
    const HH_MEMBER_VULNERABILITY = 'hhMemberVulnerability';

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

            $this->assertEquals( 2, $method->getNumberOfParameters());

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
                unset($supportedNotImplementedCalculations[array_search($method->getName(), $supportedNotImplementedCalculations)]);
            }
        }

        $this->assertEmpty($supportedNotImplementedCalculations, 'Class ' . RulesCalculation::class . ' does not contain implementation for every rule defined in ' . ScoringRulesCalculationsEnum::class);
    }

    public function testEveryMethodIsDefinedInEnum()
    {
        $customComputationReflection = new ReflectionClass(RulesCalculation::class);

        foreach ($customComputationReflection->getMethods() as $method) {
            if (!$method->isPublic()) {
                continue;
            }

            $this->assertContains($method->getName(), ScoringRulesCalculationsEnum::values(), 'There is implemented public method which is not in ' . ScoringRulesCalculationsEnum::class . '. Class ' . RulesCalculation::class . ' should contain only methods which performs calculation of rules.');
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

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesCalculationsEnum::DEPENDENCY_RATIO_UKR, 'Test');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_MID, 1));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_HIGH, 2));

        $result = $this->rulesCalculation->dependencyRatioUkr($household, $scoringRule);

        $this->assertEquals(1, $result);
    }

    public function testHhHeadGender()
    {
        $scoring = $this->getStandardSyrScoring();
        $rule = $scoring->getRuleByFieldName(ScoringRulesCalculationsEnum::HH_HEAD_GENDER);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_HEAD => true,
                    self::HH_MEMBER_GENDER => PersonGender::FEMALE,
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-16 years'),
                    self::HH_MEMBER_VULNERABILITY => VulnerabilityCriterion::CRITERION_LACTATING
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhHeadGender($household, $rule);
        $this->assertEquals(4, $result);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_HEAD => true,
                    self::HH_MEMBER_GENDER => PersonGender::MALE,
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-16 years'),
                    self::HH_MEMBER_VULNERABILITY => VulnerabilityCriterion::CRITERION_LACTATING
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhHeadGender($household, $rule);
        $this->assertEquals(1, $result);
    }

    public function testHhHeadVulnerability()
    {
        $scoring = $this->getStandardSyrScoring();
        $rule = $scoring->getRuleByFieldName(ScoringRulesCalculationsEnum::HH_HEAD_VULNERABILITY);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_HEAD => true,
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-18 years'),
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhHeadVulnerability($household, $rule);
        $this->assertEquals(0, $result);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_HEAD => true,
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-18 years +1 day'),
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhHeadVulnerability($household, $rule);
        $this->assertEquals(6, $result);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_HEAD => true,
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-60 years'),
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhHeadVulnerability($household, $rule);
        $this->assertEquals(4, $result);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_HEAD => true,
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-60 years +1 day'),
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhHeadVulnerability($household, $rule);
        $this->assertEquals(0, $result);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_HEAD => true,
                    self::HH_MEMBER_GENDER => PersonGender::FEMALE,
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-16 years'),
                    self::HH_MEMBER_VULNERABILITY => VulnerabilityCriterion::CRITERION_LACTATING
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhHeadVulnerability($household, $rule);
        $this->assertEquals(6, $result);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_HEAD => true,
                    self::HH_MEMBER_GENDER => PersonGender::MALE,
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-16 years'),
                    self::HH_MEMBER_VULNERABILITY => VulnerabilityCriterion::CRITERION_CHRONICALLY_ILL
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhHeadVulnerability($household, $rule);
        $this->assertEquals(11, $result);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_HEAD => true,
                    self::HH_MEMBER_GENDER => PersonGender::MALE,
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-61 years'),
                    self::HH_MEMBER_VULNERABILITY => VulnerabilityCriterion::CRITERION_DISABLED
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhHeadVulnerability($household, $rule);
        $this->assertEquals(9, $result);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_HEAD => true,
                    self::HH_MEMBER_GENDER => PersonGender::FEMALE,
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-44 years'),
                    self::HH_MEMBER_VULNERABILITY => VulnerabilityCriterion::CRITERION_PREGNANT
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhHeadVulnerability($household, $rule);
        $this->assertEquals(0, $result);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_HEAD => true,
                    self::HH_MEMBER_GENDER => PersonGender::MALE,
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-44 years'),
                    self::HH_MEMBER_VULNERABILITY => VulnerabilityCriterion::CRITERION_NO_VULNERABILITY
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhHeadVulnerability($household, $rule);
        $this->assertEquals(0, $result);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_HEAD => true,
                    self::HH_MEMBER_GENDER => PersonGender::FEMALE,
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-44 years'),
                    self::HH_MEMBER_VULNERABILITY => 'neco jineho'
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhHeadVulnerability($household, $rule);
        $this->assertEquals(0, $result);
    }

    public function testHhMembersVulnerability()
    {
        $scoring = $this->getStandardSyrScoring();
        $rule = $scoring->getRuleByFieldName(ScoringRulesCalculationsEnum::HH_MEMBERS_VULNERABILITY);


        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-18 years'),
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhMembersVulnerability($household, $rule);
        $this->assertEquals(0, $result);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-18 years +1 day'),
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhMembersVulnerability($household, $rule);
        $this->assertEquals(2, $result);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-60 years'),
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhMembersVulnerability($household, $rule);
        $this->assertEquals(2, $result);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-60 years +1 day'),
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhMembersVulnerability($household, $rule);
        $this->assertEquals(0, $result);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [[
                self::HH_MEMBER_BIRTH_DATE => new DateTime('-18 years +1 day'),
            ], [
                self::HH_MEMBER_BIRTH_DATE => new DateTime('-60'),
            ]],
        ]);
        $result = $this->rulesCalculation->hhMembersVulnerability($household, $rule);
        $this->assertEquals(4, $result);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_GENDER => PersonGender::FEMALE,
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-16 years'),
                    self::HH_MEMBER_VULNERABILITY => VulnerabilityCriterion::CRITERION_LACTATING
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhMembersVulnerability($household, $rule);
        $this->assertEquals(4, $result);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_GENDER => PersonGender::MALE,
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-16 years'),
                    self::HH_MEMBER_VULNERABILITY => VulnerabilityCriterion::CRITERION_CHRONICALLY_ILL
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhMembersVulnerability($household, $rule);
        $this->assertEquals(5, $result);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_GENDER => PersonGender::MALE,
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-61 years'),
                    self::HH_MEMBER_VULNERABILITY => VulnerabilityCriterion::CRITERION_DISABLED
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhMembersVulnerability($household, $rule);
        $this->assertEquals(5, $result);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_GENDER => PersonGender::FEMALE,
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-44 years'),
                    self::HH_MEMBER_VULNERABILITY => VulnerabilityCriterion::CRITERION_PREGNANT
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhMembersVulnerability($household, $rule);
        $this->assertEquals(2, $result);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_GENDER => PersonGender::MALE,
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-44 years'),
                    self::HH_MEMBER_VULNERABILITY => VulnerabilityCriterion::CRITERION_NO_VULNERABILITY
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhMembersVulnerability($household, $rule);
        $this->assertEquals(0, $result);

        $household = $this->createCustomHousehold([
            self::HH_MEMBERS => [
                [
                    self::HH_MEMBER_GENDER => PersonGender::FEMALE,
                    self::HH_MEMBER_BIRTH_DATE => new DateTime('-44 years'),
                    self::HH_MEMBER_VULNERABILITY => 'neco jineho'
                ]
            ]
        ]);
        $result = $this->rulesCalculation->hhMembersVulnerability($household, $rule);
        $this->assertEquals(0, $result);
    }

    public function testProductiveAssets()
    {
        $scoring = $this->getStandardSyrScoring();
        $rule = $scoring->getRuleByFieldName(ScoringRulesCalculationsEnum::ASSETS);

        $household = $this->createCustomHousehold([
            self::HH_ASSETS => []
        ]);
        $result = $this->rulesCalculation->productiveAssets($household, $rule);
        $this->assertEquals(-2, $result);

        $household = $this->createCustomHousehold([
            self::HH_ASSETS => [
                HouseholdAssets::AC,
            ]
        ]);
        $result = $this->rulesCalculation->productiveAssets($household, $rule);
        $this->assertEquals(-2, $result);

        $household = $this->createCustomHousehold([
            self::HH_ASSETS => [
                HouseholdAssets::AGRICULTURAL_LAND,
                HouseholdAssets::CAR,
            ]
        ]);
        $result = $this->rulesCalculation->productiveAssets($household, $rule);
        $this->assertEquals(-3, $result);

        $household = $this->createCustomHousehold([
            self::HH_ASSETS => [
                HouseholdAssets::FLATSCREEN_TV,
                HouseholdAssets::LIVESTOCK,
                HouseholdAssets::MOTORBIKE,
            ]
        ]);
        $result = $this->rulesCalculation->productiveAssets($household, $rule);
        $this->assertEquals(-4, $result);

        $household = $this->createCustomHousehold([
            self::HH_ASSETS => [
                HouseholdAssets::FLATSCREEN_TV,
                HouseholdAssets::LIVESTOCK,
                HouseholdAssets::MOTORBIKE,
                HouseholdAssets::WASHING_MACHINE
            ]
        ]);
        $result = $this->rulesCalculation->productiveAssets($household, $rule);
        $this->assertEquals(-5, $result);

        $household = $this->createCustomHousehold([
            self::HH_ASSETS => [
                HouseholdAssets::CAR,
                HouseholdAssets::FLATSCREEN_TV,
                HouseholdAssets::LIVESTOCK,
                HouseholdAssets::MOTORBIKE,
                HouseholdAssets::WASHING_MACHINE
            ]
        ]);
        $result = $this->rulesCalculation->productiveAssets($household, $rule);
        $this->assertEquals(-6, $result);
    }

    public function testCsi()
    {
        $scoring = $this->getStandardSyrScoring();
        $rule = $scoring->getRuleByFieldName(ScoringRulesCalculationsEnum::CSI);

        $household = $this->createCustomHousehold([self::HH_CSI => 0]);
        $result = $this->rulesCalculation->csi($household, $rule);
        $this->assertEquals(0, $result);

        $household = $this->createCustomHousehold([self::HH_CSI => 19]);
        $result = $this->rulesCalculation->csi($household, $rule);
        $this->assertEquals(0, $result);

        $household = $this->createCustomHousehold([self::HH_CSI => 20]);
        $result = $this->rulesCalculation->csi($household, $rule);
        $this->assertEquals(2, $result);

        $household = $this->createCustomHousehold([self::HH_CSI => 29]);
        $result = $this->rulesCalculation->csi($household, $rule);
        $this->assertEquals(2, $result);

        $household = $this->createCustomHousehold([self::HH_CSI => 30]);
        $result = $this->rulesCalculation->csi($household, $rule);
        $this->assertEquals(4, $result);

        $household = $this->createCustomHousehold([self::HH_CSI => 39]);
        $result = $this->rulesCalculation->csi($household, $rule);
        $this->assertEquals(4, $result);

        $household = $this->createCustomHousehold([self::HH_CSI => 40]);
        $result = $this->rulesCalculation->csi($household, $rule);
        $this->assertEquals(6, $result);

        $household = $this->createCustomHousehold([self::HH_CSI => 19999]);
        $result = $this->rulesCalculation->csi($household, $rule);
        $this->assertEquals(6, $result);
    }

    public function testIncomeSpentOnFood()
    {
        $scoring = $this->getStandardSyrScoring();
        $rule = $scoring->getRuleByFieldName(ScoringRulesCalculationsEnum::INCOME_SPENT_ON_FOOD);

        $household = $this->createCustomHousehold([
            self::HH_ISF => 0,
            self::HH_INCOME => 100,
        ]);
        $result = $this->rulesCalculation->incomeSpentOnFood($household, $rule);
        $this->assertEquals(3, $result);

        $household = $this->createCustomHousehold([
            self::HH_ISF => 49,
            self::HH_INCOME => 100,
        ]);
        $result = $this->rulesCalculation->incomeSpentOnFood($household, $rule);
        $this->assertEquals(3, $result);

        $household = $this->createCustomHousehold([
            self::HH_ISF => 50,
            self::HH_INCOME => 100,
        ]);
        $result = $this->rulesCalculation->incomeSpentOnFood($household, $rule);
        $this->assertEquals(4, $result);

        $household = $this->createCustomHousehold([
            self::HH_ISF => 64,
            self::HH_INCOME => 100,
        ]);
        $result = $this->rulesCalculation->incomeSpentOnFood($household, $rule);
        $this->assertEquals(4, $result);

        $household = $this->createCustomHousehold([
            self::HH_ISF => 65,
            self::HH_INCOME => 100,
        ]);
        $result = $this->rulesCalculation->incomeSpentOnFood($household, $rule);
        $this->assertEquals(5, $result);

        $household = $this->createCustomHousehold([
            self::HH_ISF => 74,
            self::HH_INCOME => 100,
        ]);
        $result = $this->rulesCalculation->incomeSpentOnFood($household, $rule);
        $this->assertEquals(5, $result);

        $household = $this->createCustomHousehold([
            self::HH_ISF => 75,
            self::HH_INCOME => 100,
        ]);
        $result = $this->rulesCalculation->incomeSpentOnFood($household, $rule);
        $this->assertEquals(6, $result);

        $household = $this->createCustomHousehold([
            self::HH_ISF => 1000,
            self::HH_INCOME => 100,
        ]);
        $result = $this->rulesCalculation->incomeSpentOnFood($household, $rule);
        $this->assertEquals(6, $result);
    }

    public function testFoodConsumptionScore()
    {
        $scoring = $this->getStandardSyrScoring();
        $rule = $scoring->getRuleByFieldName(ScoringRulesCalculationsEnum::FCS);

        $household = $this->createCustomHousehold([self::HH_FCS => 0]);
        $result = $this->rulesCalculation->fcs($household, $rule);
        $this->assertEquals(6, $result);

        $household = $this->createCustomHousehold([self::HH_FCS => 20]);
        $result = $this->rulesCalculation->fcs($household, $rule);
        $this->assertEquals(6, $result);

        $household = $this->createCustomHousehold([self::HH_FCS => 21]);
        $result = $this->rulesCalculation->fcs($household, $rule);
        $this->assertEquals(4, $result);

        $household = $this->createCustomHousehold([self::HH_FCS => 35]);
        $result = $this->rulesCalculation->fcs($household, $rule);
        $this->assertEquals(4, $result);

        $household = $this->createCustomHousehold([self::HH_FCS => 36]);
        $result = $this->rulesCalculation->fcs($household, $rule);
        $this->assertEquals(1, $result);

        $household = $this->createCustomHousehold([self::HH_FCS => 9999]);
        $result = $this->rulesCalculation->fcs($household, $rule);
        $this->assertEquals(1, $result);
    }

    public function testDebt()
    {
        $scoring = $this->getStandardSyrScoring();
        $rule = $scoring->getRuleByFieldName(ScoringRulesCalculationsEnum::DEBT);

        $household = $this->createCustomHousehold([self::HH_DEBT_LEVEL => 1]);
        $result = $this->rulesCalculation->debt($household, $rule);
        $this->assertEquals(1, $result);

        $household = $this->createCustomHousehold([self::HH_DEBT_LEVEL => 2]);
        $result = $this->rulesCalculation->debt($household, $rule);
        $this->assertEquals(2, $result);

        $household = $this->createCustomHousehold([self::HH_DEBT_LEVEL => 3]);
        $result = $this->rulesCalculation->debt($household, $rule);
        $this->assertEquals(3, $result);

        $household = $this->createCustomHousehold([self::HH_DEBT_LEVEL => 4]);
        $result = $this->rulesCalculation->debt($household, $rule);
        $this->assertEquals(4, $result);

        $household = $this->createCustomHousehold([self::HH_DEBT_LEVEL => 5]);
        $result = $this->rulesCalculation->debt($household, $rule);
        $this->assertEquals(5, $result);
    }

        public function testAssistanceProvided()
    {
        $scoring = $this->getStandardSyrScoring();
        $rule = $scoring->getRuleByFieldName(ScoringRulesCalculationsEnum::ASSISTANCE_PROVIDED);

        $household = $this->createCustomHousehold([
            self::HH_SUPPORT_DATE_RECEIVED => new DateTime('-2 months'),
            self::HH_SUPPORT_RECEIVED_TYPES => [
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
            ],
        ]);
        $result = $this->rulesCalculation->assistanceProvided($household, $rule);
        $this->assertEquals(-30, $result);

        $household = $this->createCustomHousehold([
            self::HH_SUPPORT_DATE_RECEIVED => new DateTime('-3 months'),
            self::HH_SUPPORT_RECEIVED_TYPES => [
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
            ],
        ]);
        $result = $this->rulesCalculation->assistanceProvided($household, $rule);
        $this->assertEquals(0, $result);
    }

    private function getStandardSyrScoring(): Scoring
    {
        $rules = [];

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesCalculationsEnum::HH_HEAD_VULNERABILITY, 'Vulnerability Head of Household');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::CHRONICALLY_ILL, 5));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DISABLED, 5));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::INFANT, 6));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::ELDERLY, 4));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::PREGNANT_LACTATING_FEMALE, 0));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::NO_VULNERABILITY, 0));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::OTHER, 0));
        $rules[] = $scoringRule;

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesCalculationsEnum::HH_MEMBERS_VULNERABILITY, 'Vulnerability of Household Members');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::CHRONICALLY_ILL, 3));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DISABLED, 3));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::INFANT, 2));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::ELDERLY, 2));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::PREGNANT_LACTATING_FEMALE, 2));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::NO_VULNERABILITY, 0));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::OTHER, 0));
        $rules[] = $scoringRule;

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesCalculationsEnum::ASSETS, 'Productive Assets');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::ASSETS_0_1, -2));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::ASSETS_2, -3));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::ASSETS_3, -4));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::ASSETS_4, -5));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::ASSETS_5_MORE, -6));
        $rules[] = $scoringRule;

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesCalculationsEnum::CSI, 'Coping Strategy Index (CSI)');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::CSI_0_20, 0));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::CSI_20_30, 2));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::CSI_30_40, 4));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::CSI_40_MORE, 6));
        $rules[] = $scoringRule;

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesCalculationsEnum::INCOME_SPENT_ON_FOOD, 'Income spent on food');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::INCOME_SPENT_0_50, 3));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::INCOME_SPENT_50_65, 4));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::INCOME_SPENT_65_75, 5));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::INCOME_SPENT_75_MORE, 6));
        $rules[] = $scoringRule;

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesCalculationsEnum::FCS, 'Food consumption score');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::CONSUMPTION_POOR, 6));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::CONSUMPTION_BORDERLINE, 4));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::CONSUMPTION_ACCEPTABLE, 1));
        $rules[] = $scoringRule;

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesCalculationsEnum::HH_HEAD_GENDER, 'Gender of Head of Household');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::GENDER_FEMALE, 4));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::GENDER_MALE, 1));
        $rules[] = $scoringRule;

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesCalculationsEnum::DEBT, 'Debt');
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DEBT_LEVEL_1, 1));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DEBT_LEVEL_2, 2));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DEBT_LEVEL_3, 3));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DEBT_LEVEL_4, 4));
        $scoringRule->addOption(new ScoringRuleOption(ScoringRuleCalculationOptionsEnum::DEBT_LEVEL_5, 5));
        $rules[] = $scoringRule;

        $scoringRule = new ScoringRule(ScoringRuleType::CALCULATION, ScoringRulesCalculationsEnum::ASSISTANCE_PROVIDED, 'Assistance Provided in the Last 3 Months');
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

    private function createCustomHousehold(array $params): Household
    {
        if (!$params || !is_array($params) || count($params) === 0) {
            return $this->getStandardSyrHousehold();
        }

        $household = new Household();

        if(array_key_exists(self::HH_MEMBERS,$params) && is_array($params[self::HH_MEMBERS])) {
            foreach ($params[self::HH_MEMBERS] as $member) {
                $beneficiary = new Beneficiary();

                $beneficiary->setHead($member[self::HH_MEMBER_HEAD] ?? false);
                $beneficiary->getPerson()->setGender($member[self::HH_MEMBER_GENDER] ?? PersonGender::MALE);
                $beneficiary->getPerson()->setDateOfBirth($member[self::HH_MEMBER_BIRTH_DATE] ?? (new DateTime('-44 year')));

                if(array_key_exists(self::HH_MEMBER_VULNERABILITY, $member)) {
                    $vulnerabilityCriteria = new ArrayCollection();
                    $vulnerabilityCriteria->add(new VulnerabilityCriterion($member[self::HH_MEMBER_VULNERABILITY]));
                    $beneficiary->setVulnerabilityCriteria($vulnerabilityCriteria);
                }

                $household->addBeneficiary($beneficiary);
            }
        } else {
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

        }
        //shelter type
        if(array_key_exists(self::HH_SHELTER_STATUS,$params)) {
            $household->setShelterStatus($params[self::HH_SHELTER_STATUS]);
        }

        //assets
        if(array_key_exists(self::HH_ASSETS,$params) && is_array($params[self::HH_ASSETS])) {
            $household->setAssets($params[self::HH_ASSETS]);
        }

        //csi
        if(array_key_exists(self::HH_CSI,$params)) {
            $household->setCopingStrategiesIndex($params[self::HH_CSI]);
        }

        //income
        if(array_key_exists(self::HH_INCOME,$params)) {
            $household->setIncome($params[self::HH_INCOME]);
        }

        //income spent on food
        if(array_key_exists(self::HH_ISF,$params)) {
            $household->setIncomeSpentOnFood($params[self::HH_ISF]);
        }

        //food consumption score
        if(array_key_exists(self::HH_FCS,$params)) {
            $household->setFoodConsumptionScore($params[self::HH_FCS]);
        }

        //debt
        if(array_key_exists(self::HH_DEBT_LEVEL,$params)) {
            $household->setDebtLevel($params[self::HH_DEBT_LEVEL]);
        }

        //assistance provided in the last 3 months
        if(array_key_exists(self::HH_SUPPORT_RECEIVED_TYPES,$params) && is_array($params[self::HH_SUPPORT_RECEIVED_TYPES])) {
            $household->setSupportReceivedTypes($params[self::HH_SUPPORT_RECEIVED_TYPES]);
        }

        if(array_key_exists(self::HH_SUPPORT_DATE_RECEIVED,$params) && $params[self::HH_SUPPORT_DATE_RECEIVED] instanceof \DateTimeInterface) {
            $household->setSupportDateReceived($params[self::HH_SUPPORT_DATE_RECEIVED]);
        }

        return $household;
    }

    public function testEnumHouseholdShelterStatus()
    {
        $scoringRule = new ScoringRule('enum', 'HouseholdShelterStatus', 'Test');
        $scoringRule->addOption(new ScoringRuleOption('House/Apartment - Lightly Damaged',1));
        $scoringRule->addOption(new ScoringRuleOption('House/Apartment - Moderately Damaged',4));
        $scoringRule->addOption(new ScoringRuleOption('House/Apartment - Severely Damaged',5));

        
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
}
