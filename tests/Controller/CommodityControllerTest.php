<?php

namespace Tests\Controller;

use Entity\Assistance;
use Entity\Commodity;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Tests\BMSServiceTestCase;

class CommodityControllerTest extends BMSServiceTestCase
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
        $this->client = self::getContainer()->get('test.client');
    }

    /**
     * @throws Exception
     */
    public function testGetCommodities()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $commodity1 = $em->getRepository(Commodity::class)->findBy([], ['id' => 'asc'])[0];
        $commodity2 = $em->getRepository(Commodity::class)->findBy([], ['id' => 'asc'])[1];

        $this->request(
            'GET',
            '/api/basic/web-app/v1/assistances/commodities?filter[id][]=' . $commodity1->getId(
            ) . '&filter[id][]=' . $commodity2->getId()
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "totalCount": 2,
            "data": [
                {
                    "id": ' . $commodity1->getId() . ',
                    "modalityType": "*",
                    "unit": "*",
                    "value": "*",
                    "description": "*",
                    "division": "*"
                },
                {
                    "id": ' . $commodity2->getId() . ',
                    "modalityType": "*",
                    "unit": "*",
                    "value": "*",
                    "description": "*",
                    "division": "*"
                }
            ]}',
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetCommoditiesByAssistance()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $assistance = $em->getRepository(Assistance::class)->findBy(['archived' => 0], ['id' => 'asc'])[0];

        $this->request('GET', '/api/basic/web-app/v1/assistances/' . $assistance->getId() . '/commodities');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "totalCount": ' . count($assistance->getCommodities()) . ',
            "data": [
                {
                    "id": "*",
                    "modalityType": "*",
                    "unit": "*",
                    "value": "*",
                    "description": "*",
                    "division": "*"
                }
            ]}',
            $this->client->getResponse()->getContent()
        );
    }
}
