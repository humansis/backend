<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use DistributionBundle\Enum\AssistanceType;
use Exception;
use ProjectBundle\DBAL\SubSectorEnum;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class AssistanceCodelistControllerTest extends AbstractFunctionalApiTest
{
    /**
     * @throws Exception
     */
    public function testGetTargets()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/assistances/targets?filter[type]=' . AssistanceType::ACTIVITY, [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/assistances/types?filter[subsector]=' . SubSectorEnum::FOOD_CASH_FOR_WORK, [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }
}
