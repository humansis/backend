<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller\OfflineApp;

use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class ProjectControllerTest extends AbstractFunctionalApiTest
{
    public function testGetList()
    {
        $this->client->request('GET', '/api/basic/offline-app/v2/projects', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('[{
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
         }]', $this->client->getResponse()->getContent());
    }
}
