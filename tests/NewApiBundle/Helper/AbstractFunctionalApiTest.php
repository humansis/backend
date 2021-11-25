<?php

namespace Tests\NewApiBundle\Helper;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractFunctionalApiTest extends WebTestCase
{
    const USER_TESTER = 'test@example.org';
    const USER_TESTER_VENDOR = 'vendor.eth@example.org';

    use AssertIterablesTrait;
    use TesterUserTrait;

    /** @var KernelBrowser */
    protected $client;

    public function setUp()
    {
        parent::setUp();
        $this->client = static::createClient();
    }
}
