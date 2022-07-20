<?php

namespace Tests\NewApiBundle\Controller;

use NewApiBundle\DBAL\AssistanceTypeEnum;
use NewApiBundle\Entity\Assistance;
use NewApiBundle\Enum\AssistanceTargetType;
use NewApiBundle\Enum\AssistanceType;
use Exception;
use NewApiBundle\Component\Assistance\Enum\CommodityDivision;
use NewApiBundle\DBAL\SubSectorEnum;
use Tests\BMSServiceTestCase;

class AssistanceCodelistControllerTest extends BMSServiceTestCase
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
    

    /**
     * @throws Exception
     */
    public function testGetTargets()
    {
        $this->request('GET', '/api/basic/web-app/v1/assistances/targets?filter[type]=' . AssistanceType::ACTIVITY);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }

    /**
     * @throws Exception
     */
    public function testGetAssistanceTypes()
    {
        $this->request('GET', '/api/basic/web-app/v1/assistances/types?filter[subsector]=' . SubSectorEnum::FOOD_CASH_FOR_WORK);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }

    /**
     * @throws Exception
     */
    public function testGetCommodityDivisions()
    {
        $this->request('GET', '/api/basic/web-app/v1/assistances/commodity/divisions');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);

        $this->assertEquals(array_column($result['data'], 'code'), CommodityDivision::values());
    }
}
