<?php

namespace Tests\NewApiBundle\Secret;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SecretTest extends KernelTestCase
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
        $this->assertGreaterThan(0, strlen($secret));
    }
}
