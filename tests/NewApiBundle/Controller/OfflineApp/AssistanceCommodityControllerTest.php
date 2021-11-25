<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller\OfflineApp;

use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class AssistanceCommodityControllerTest extends AbstractFunctionalApiTest
{
    public function testGet()
    {
        $this->client->request('GET', '/api/basic/offline-app/v2/commodities', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('[{
            "id": "*",
            "modalityType": "*",
            "unit": "*",
            "value": "*",
            "description": "*"
        }]', $this->client->getResponse()->getContent());
    }

    public function testGetFilteredByModalityTypes()
    {
        $this->client->request('GET', '/api/basic/offline-app/v2/commodities?filter[notModalityTypes][]=Cash', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('[{
            "id": "*",
            "modalityType": "*",
            "unit": "*",
            "value": "*",
            "description": "*"
        }]', $this->client->getResponse()->getContent());
    }
}
