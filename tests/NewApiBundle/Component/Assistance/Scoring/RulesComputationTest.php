<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Assistance\Scoring;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use DateTime;
use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringRuleOptionsEnum;
use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringRulesEnum;
use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringRuleType;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringRuleOption;
use NewApiBundle\Component\Assistance\Scoring\RulesCalculation;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringRule;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RulesComputationTest extends KernelTestCase
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
}
