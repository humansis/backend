<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Assistance\Scoring\Model;

use NewApiBundle\Component\Assistance\Scoring\Model\Protocol;
use PHPStan\Testing\TestCase;

class ProtocolTest extends TestCase
{
    public function testSerialize()
    {
        $protocol = new Protocol();

        $protocol->addScore('test', 5);

        /** @var Protocol $recreatedProtocol */
        $recreatedProtocol = unserialize(serialize($protocol));

        $this->assertEquals(1, count($recreatedProtocol->getAllScores()));
        $this->assertEquals(5, $recreatedProtocol->getScore('test'));
    }
}
