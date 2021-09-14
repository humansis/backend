<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Api;

use NewApiBundle\Api\ReflexiveFiller;
use PHPUnit\Framework\TestCase;

class ReflexiveFillerTest extends TestCase
{
    public function testSimpleValueTransfer(): void
    {
        $target = new DummyEntityObject();

        $testSources = [];

        $reflexive = new DummyEntityObject();
        $reflexive->setArchived(true);
        $reflexive->setSimpleName('nameXXX');
        $testSources['reflexive'] = $reflexive;

        $another = new DummyEntityObject2();
        $another->setArchived(true);
        $another->setSimpleName('nameXXX');
        $testSources['another'] = $another;

        foreach ($testSources as $sourceName => $source) {
            $filler = new ReflexiveFiller();
            $filler->fillBy($target, $source);
            $this->assertEquals(true, $target->isArchived(), "$sourceName has wrong archived attribute");
            $this->assertEquals('nameXXX', $target->getSimpleName(), "$sourceName has wrong name attribute");
        }
    }
}
