<?php

namespace Tests\NewApiBundle\Secret;

use PHPUnit\Framework\TestCase;

class SecretTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
    }

    public function testSecretIsNotEmpty()
    {
        $kernel = self::bootKernel();
        $secret = $kernel->getContainer()->getParameter('secret');
        $this->assertNotEmpty($secret);
        $this->assertNotNull($secret);
        $this->assertIsString($secret);
    }
}
