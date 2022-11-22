<?php

namespace Tests\Controller\OfflineApp;

use Exception;
use Tests\BMSServiceTestCase;

class AssistanceCommodityControllerTest extends BMSServiceTestCase
{
    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    public function testGet()
    {
        $this->request('GET', '/api/basic/offline-app/v2/commodities');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '[{
            "id": "*",
            "modalityType": "*",
            "unit": "*",
            "value": "*",
            "description": "*"
        }]',
            $this->client->getResponse()->getContent()
        );
    }

    public function testGetFilteredByModalityTypes()
    {
        $this->request('GET', '/api/basic/offline-app/v2/commodities?filter[notModalityTypes][]=Cash');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '[{
            "id": "*",
            "modalityType": "*",
            "unit": "*",
            "value": "*",
            "description": "*"
        }]',
            $this->client->getResponse()->getContent()
        );
    }
}
