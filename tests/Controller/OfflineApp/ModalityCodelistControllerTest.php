<?php

namespace Tests\Controller\OfflineApp;

use Tests\BMSServiceTestCase;

class ModalityCodelistControllerTest extends BMSServiceTestCase
{
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::getContainer()->get('test.client');
    }

    public function testGetModalities()
    {
        $this->request('GET', '/api/basic/offline-app/v1/modality-types');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(' [{"code": "*", "value": "*"}]', $this->client->getResponse()->getContent());
    }
}
