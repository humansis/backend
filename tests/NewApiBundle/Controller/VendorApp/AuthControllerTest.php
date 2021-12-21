<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller\VendorApp;

use Tests\BMSServiceTestCase;

class AuthControllerTest extends BMSServiceTestCase
{
    public function setUp()
    {
        // Configuration of BMSServiceTest
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    public function testOfflineAppLogin(): void
    {
        $body = [
            'username' => 'admin@example.org',
            'password' => 'pin1234',
        ];

        $this->client->request('POST', '/api/jwt/vendor-app/v2/login', [], [], [], json_encode($body));

        $responseBody = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $this->assertTrue(gettype($responseBody) == 'array');
        $this->assertArrayHasKey('userId', $responseBody);
        $this->assertArrayHasKey('username', $responseBody);
        $this->assertArrayHasKey('token', $responseBody);
        $this->assertArrayHasKey('location', $responseBody);
    }
}
