<?php

declare(strict_types=1);

namespace Tests\Controller\VendorApp;

use Tests\BMSServiceTestCase;

class AuthControllerTest extends BMSServiceTestCase
{
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    public function testOfflineAppLogin(): never
    {
        $this->markTestSkipped('Support for JWT in test environment needs to be done first');

        $body = [
            'username' => 'vendor.syr@example.org',
            'password' => 'pin1234',
        ];

        $this->client->request('POST', '/api/jwt/vendor-app/v2/login', [], [], [], json_encode($body, JSON_THROW_ON_ERROR));

        $responseBody = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            "Request failed: " . $this->client->getResponse()->getContent()
        );
        $this->assertTrue(gettype($responseBody) == 'array');
        $this->assertArrayHasKey('id', $responseBody);
        $this->assertArrayHasKey('vendorId', $responseBody);
        $this->assertArrayHasKey('username', $responseBody);
        $this->assertArrayHasKey('token', $responseBody);
        $this->assertArrayHasKey('countryISO3', $responseBody);
    }
}
