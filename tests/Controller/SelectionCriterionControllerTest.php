<?php

namespace Tests\Controller;

use Exception;
use Enum\SelectionCriteriaTarget;
use Tests\BMSServiceTestCase;

class SelectionCriterionControllerTest extends BMSServiceTestCase
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
        $this->client = self::$container->get('test.client');
    }

    /**
     * @throws Exception
     */
    public function testGetTargets()
    {
        $this->request('GET', '/api/basic/web-app/v1/selection-criteria/targets');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJson(
            '{
            "totalCount": 3,
            "data": [
                {"code": "Beneficiary", "value": "Beneficiary"},
                {"code": "Head", "value": "Head"},
                {"code": "Household", "value": "Household"}
            ]
        }',
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetFields()
    {
        $this->request(
            'GET',
            '/api/basic/web-app/v1/selection-criteria/targets/' . SelectionCriteriaTarget::BENEFICIARY . '/fields',
            ['country' => 'KHM']
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "totalCount": "*",
            "data": [
                {"code": "gender", "type": "gender", "value": "Gender"},
                {"code": "dateOfBirth", "type": "date", "value": "Date of Birth"},
                {"code": "hasNotBeenInDistributionsSince", "type": "date", "value": "Has Not Been in a Distribution Since"}
            ]
        }',
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetConditions()
    {
        $this->request(
            'GET',
            '/api/basic/web-app/v1/selection-criteria/targets/' . SelectionCriteriaTarget::BENEFICIARY . '/fields/gender/conditions',
            ['country' => 'KHM']
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "totalCount": 1,
            "data": [
                {"code": "=", "value": "="}
            ]
        }',
            $this->client->getResponse()->getContent()
        );
    }
}
