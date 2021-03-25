<?php

namespace Tests\NewApiBundle\Controller;

use Exception;
use NewApiBundle\Enum\SelectionCriteriaTarget;
use Tests\BMSServiceTestCase;

class SelectionCriterionControllerTest extends BMSServiceTestCase
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
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/selection-criteria/targets');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJson('{
            "totalCount": 3,
            "data": [
                {"code": "Beneficiary", "value": "Beneficiary"},
                {"code": "Head", "value": "Head"},
                {"code": "Household", "value": "Household"}
            ]
        }', $this->client->getResponse()->getContent());
    }

    /**
     * @throws Exception
     */
    public function testGetFields()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/selection-criteria/targets/'.SelectionCriteriaTarget::BENEFICIARY.'/fields', ['country' => 'KHM']);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": [
                {"code": "gender", "type": "gender"},
                {"code": "dateOfBirth", "type": "date"},
                {"code": "hasNotBeenInDistributionsSince", "type": "boolean"}
            ]
        }', $this->client->getResponse()->getContent());
    }

    /**
     * @throws Exception
     */
    public function testGetConditions()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $this->request('GET', '/api/basic/selection-criteria/targets/'.SelectionCriteriaTarget::BENEFICIARY.'/fields/gender/conditions', ['country' => 'KHM']);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": 1,
            "data": [
                {"code": "=", "value": "="}
            ]
        }', $this->client->getResponse()->getContent());
    }
}
