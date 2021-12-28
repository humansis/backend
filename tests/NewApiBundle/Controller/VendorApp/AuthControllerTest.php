<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller\VendorApp;

use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class AuthControllerTest extends AbstractFunctionalApiTest
{
    public function testOfflineAppLogin(): void
    {
        $this->markTestSkipped('Support for JWT in test environment needs to be done first');

        $body = [
            'username' => 'vendor.syr@example.org',
            'password' => 'pin1234',
        ];

        $this->client->request('POST', '/api/jwt/vendor-app/v2/login', [], [], [], json_encode($body));

        $responseBody = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $this->assertTrue(gettype($responseBody) == 'array');
        $this->assertArrayHasKey('id', $responseBody);
        $this->assertArrayHasKey('username', $responseBody);
        $this->assertArrayHasKey('token', $responseBody);
        $this->assertArrayHasKey('countryISO3', $responseBody);
    }
}
