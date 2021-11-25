<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use Exception;
use NewApiBundle\Enum\SelectionCriteriaTarget;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class SelectionCriterionControllerTest extends AbstractFunctionalApiTest
{
    /**
     * @throws Exception
     */
    public function testGetTargets()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/selection-criteria/targets', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/selection-criteria/targets/'.SelectionCriteriaTarget::BENEFICIARY.'/fields', ['country' => 'KHM'], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": [
                {"code": "gender", "type": "gender", "value": "Gender"},
                {"code": "dateOfBirth", "type": "date", "value": "Date of Birth"},
                {"code": "hasNotBeenInDistributionsSince", "type": "date", "value": "Has Not Been in a Distribution Since"}
            ]
        }', $this->client->getResponse()->getContent());
    }

    /**
     * @throws Exception
     */
    public function testGetConditions()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/selection-criteria/targets/'.SelectionCriteriaTarget::BENEFICIARY.'/fields/gender/conditions', ['country' => 'KHM'], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "totalCount": 1,
            "data": [
                {"code": "=", "value": "="}
            ]
        }', $this->client->getResponse()->getContent());
    }
}
