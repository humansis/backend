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
            $filler->fill($target, $source);
            $this->assertEquals(true, $target->isArchived(), "$sourceName has wrong archived attribute");
            $this->assertEquals('nameXXX', $target->getSimpleName(), "$sourceName has wrong name attribute");
        }
    }

    public function testPropertyToPropertyTransfer(): void
    {
        $target = new DummyEntityObject();

        $source = new DummyEntityObject3();
        $source->setRemoved(true);
        $source->setVeryLongname('nameXXX');

        $filler = new ReflexiveFiller();
        $filler->map('removed', 'archived');
        $filler->map('veryLong_name', 'simpleName');
        $filler->map('things', 'items');
        $filler->fill($target, $source);
        $this->assertEquals(true, $target->isArchived(), "wrong archived attribute");
        $this->assertEquals('nameXXX', $target->getSimpleName(), "wrong name attribute");
    }

    public function testIgnorePropertyToPropertyTransfer(): void
    {
        $target = new DummyEntityObject();

        $source = new DummyEntityObject3();
        $source->setRemoved(true);
        $source->setVeryLongname('nameXXX');

        $filler = new ReflexiveFiller();
        $filler->map('removed', 'archived');
        $filler->ignore(['veryLong_name', 'things']);
        $filler->fill($target, $source);
        $this->assertEquals(true, $target->isArchived(), "wrong archived attribute");
        $this->assertEquals('placeholder', $target->getSimpleName(), "wrong name attribute");
    }
}
