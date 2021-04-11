<?php

namespace Tests\NewApiBundle\Controller;

use Exception;
use Tests\BMSServiceTestCase;
use TransactionBundle\Entity\Transaction;

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

    public function testListByAssistanceAndBeneficiary()
    {
        /** @var Transaction $item */
        $item = self::$container->get('doctrine')->getRepository(Transaction::class)->findBy([])[0];
        $assistanceId = $item->getAssistanceBeneficiary()->getAssistance()->getId();
        $beneficiaryId = $item->getAssistanceBeneficiary()->getBeneficiary()->getId();

        $this->request('GET', '/api/basic/assistances/'.$assistanceId.'/beneficiaries/'.$beneficiaryId.'/transactions');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": ["*"]
        }', $this->client->getResponse()->getContent());
    }

    public function testListOfStatuses()
    {
        $this->request('GET', '/api/basic/transactions/statuses');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": 3,
            "data": [
               {"code": "0", "value": "Failure"},
               {"code": "1", "value": "Success"},
               {"code": "2", "value": "No Phone"}
            ]
        }', $this->client->getResponse()->getContent());
    }
}
