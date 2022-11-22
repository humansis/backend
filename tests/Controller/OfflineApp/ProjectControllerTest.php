<?php

namespace Tests\Controller\OfflineApp;

use Exception;
use Tests\BMSServiceTestCase;

class ProjectControllerTest extends BMSServiceTestCase
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

    public function testGetList()
    {
        $this->request('GET', '/api/basic/offline-app/v2/projects');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '[{
            "id": "*",
            "name": "*",
            "internalId": "*",
            "iso3": "*",
            "notes": "*",
            "target": "*",
            "startDate": "*",
            "endDate": "*",
            "sectors": "*",
            "donorIds": "*",
            "numberOfHouseholds": "*"
         }]',
            $this->client->getResponse()->getContent()
        );
    }
}
