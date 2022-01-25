<?php

namespace Tests\NewApiBundle\Controller;

use DistributionBundle\Entity\Assistance;
use Exception;
use Tests\BMSServiceTestCase;

class AssistanceStatisticsControllerTest extends BMSServiceTestCase
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

    public function testStatistics()
    {
        /** @var Assistance $assistance */
        $assistance = self::$container->get('doctrine')->getRepository(Assistance::class)->findBy([], ['id' => 'asc'])[0];

        $this->request('GET', '/api/basic/web-app/v1/assistances/'.$assistance->getId().'/statistics');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "id": '.$assistance->getId().',
            "numberOfBeneficiaries": "*",
            "amountTotal": "*",
            "amountDistributed": "*",
            "amountUsed": "*",
            "amountSent": "*",
            "amountPickedUp": "*"
        }', $this->client->getResponse()->getContent());
    }

    public function testList()
    {
        /** @var Assistance $assistance */
        $assistance = self::$container->get('doctrine')->getRepository(Assistance::class)->findBy(['archived' => false], ['id' => 'asc'])[0];

        $this->request('GET', '/api/basic/web-app/v1/assistances/statistics?filter[id][]='.$assistance->getId(), ['country' => 'KHM']);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }
}
