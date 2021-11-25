<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller\OfflineApp;

use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class ModalityCodelistControllerTest extends AbstractFunctionalApiTest
{
    public function testGetModalities()
    {
        $this->client->request('GET', '/api/basic/offline-app/v1/modality-types', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment(' [{"code": "*", "value": "*"}]', $this->client->getResponse()->getContent());
    }
}
