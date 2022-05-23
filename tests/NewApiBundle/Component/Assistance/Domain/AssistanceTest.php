<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Assistance\Domain;

use NewApiBundle\Component\Assistance\AssistanceFactory;
use NewApiBundle\Component\Assistance\Domain\Assistance;
use PHPUnit\Framework\TestCase;

class AssistanceTest extends TestCase
{
    /** @var Assistance */
    private $assistance;

    protected function setUp()
    {
        $assistanceFactory = new AssistanceFactory(
            $this->createMock(Ca)
        );
        $this->assistance = $assistanceFactory->create();
    }

    public function testAddBeneficiary()
    {

    }

    public function testRemoveBeneficiary()
    {

    }

    public function testGetCommoditiesSummary()
    {

    }
}
