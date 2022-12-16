<?php

declare(strict_types=1);

namespace Component\Assistance\Scoring;

use Entity\Beneficiary;
use Entity\Household;
use PHPUnit\Framework\TestCase;

class ScoringComputedValue extends TestCase
{
    private ScoringComputedValues $computedValues;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct(
            $name,
            $data,
            $dataName
        );

        $this->computedValues = new ScoringComputedValues();
    }

    public function testNumberOfChildrenInHousehold(): void
    {
        $household = new Household();

        $count = $this->computedValues->numberOfChildrenInHousehold($household);
        $this->assertEquals(0, $count);

        $child = new Beneficiary();
        $child->getPerson()->setDateOfBirth((new \DateTime())->modify('-10 years'));

        $household->addBeneficiary($child);
        $household->addBeneficiary(clone $child);

        $count = $this->computedValues->numberOfChildrenInHousehold($household);
        $this->assertEquals(2, $count);
    }

    public function testIncomePerMember(): void
    {
        $household = new Household();

        $income = $this->computedValues->incomePerMember($household);
        $this->assertNull($income);

        $household->setIncome(1000);

        $income = $this->computedValues->incomePerMember($household);
        $this->assertNull($income);

        $household->addBeneficiary(new Beneficiary());
        $household->addBeneficiary(new Beneficiary());

        $income = $this->computedValues->incomePerMember($household);
        $this->assertEquals(500, $income);
    }
}
