<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class TransactionControllerTest extends AbstractFunctionalApiTest
{
    public function testListOfStatuses()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/transactions/statuses', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "totalCount": 4,
            "data": [
               {"code": "0", "value": "Failure"},
               {"code": "1", "value": "Success"},
               {"code": "2", "value": "No Phone"},
               {"code": "3", "value": "Canceled"}
            ]
        }', $this->client->getResponse()->getContent());
    }
}
