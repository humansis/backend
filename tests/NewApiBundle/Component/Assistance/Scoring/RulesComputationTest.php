<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Assistance\Scoring;

use BeneficiaryBundle\Entity\Household;
use NewApiBundle\Component\Assistance\Scoring\RulesCalculation;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringRule;
use PHPUnit\Framework\TestCase;

class RulesComputationTest extends TestCase
{
    public function testCorrectMethodsFormat()
    {
        $customComputationReflection = new \ReflectionClass(RulesCalculation::class);

        foreach ($customComputationReflection->getMethods() as $method) {
            $this->assertEquals( 2, $method->getNumberOfParameters());

            $this->assertEquals(Household::class, $method->getParameters()[0]->getClass()->getName());
            $this->assertEquals(ScoringRule::class, $method->getParameters()[1]->getClass()->getName());
        }
    }
}
