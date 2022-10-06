<?php

declare(strict_types=1);

namespace Tests\Component\Assistance\Scoring\Model;

use Component\Assistance\Scoring\Model\ScoringProtocol;
use PHPStan\Testing\TestCase;

class ProtocolTest extends TestCase
{
    public function testSerialize()
    {
        $protocol = new ScoringProtocol();

        $protocol->addScore('test', 5);

        /** @var ScoringProtocol $recreatedProtocol */
        $recreatedProtocol = unserialize(serialize($protocol));

        $this->assertEquals(1, count($recreatedProtocol->getAllScores()));
        $this->assertEquals(5, $recreatedProtocol->getScore('test'));
    }
}
