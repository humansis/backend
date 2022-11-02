<?php

declare(strict_types=1);

namespace Tests\Component\Assistance\Scoring\Model;

use Component\Assistance\Scoring\Model\ScoringProtocol;
use PHPStan\Testing\PHPStanTestCase;

class ProtocolTest extends PHPStanTestCase
{
    public function testSerialize(): void
    {
        $protocol = new ScoringProtocol();

        $protocol->addScore('test', 5);

        /** @var ScoringProtocol $recreatedProtocol */
        $recreatedProtocol = unserialize(serialize($protocol));

        $this->assertCount(1, $recreatedProtocol->getAllScores());
        $this->assertEquals(5, $recreatedProtocol->getScore('test'));
    }
}
