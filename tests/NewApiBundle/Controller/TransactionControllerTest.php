<?php

namespace Tests\NewApiBundle\Controller;

use Exception;
use Tests\BMSServiceTestCase;
use NewApiBundle\Entity\Transaction;

class TransactionControllerTest extends BMSServiceTestCase
{
    /**
     * @throws Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    public function testListOfStatuses()
    {
        $this->request('GET', '/api/basic/web-app/v1/transactions/statuses');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
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
