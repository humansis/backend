<?php

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\HouseholdLocation;
use BeneficiaryBundle\Enum\HouseholdAssets;
use Exception;
use ProjectBundle\Enum\Livelihood;
use Tests\BMSServiceTestCase;

class HouseholdCodelistControllerTest extends BMSServiceTestCase
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
    public function testGetLivelihoods()
    {
        $this->request('GET', '/api/basic/web-app/v1/households/livelihoods');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(Livelihood::values()), $result['totalCount']);
    }

    /**
     * @throws Exception
     */
    public function testGetAssets()
    {
        $this->request('GET', '/api/basic/web-app/v1/households/assets');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(HouseholdAssets::all()), $result['totalCount']);
    }

    /**
     * @throws Exception
     */
    public function testGetSupportReceivedTypes()
    {
        $this->request('GET', '/api/basic/web-app/v1/households/support-received-types');

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "totalCount": '.count(Household::SUPPORT_RECIEVED_TYPES).',
            "data": "*"
        }', $this->client->getResponse()->getContent());
    }

    /**
     * @throws Exception
     */
    public function testGetShelterStatuses()
    {
        $this->request('GET', '/api/basic/web-app/v1/households/shelter-statuses');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(Household::SHELTER_STATUSES), $result['totalCount']);
    }

    /**
     * @throws Exception
     */
    public function testGetLocationTypes()
    {
        $this->request('GET', '/api/basic/web-app/v1/households/locations/types');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(HouseholdLocation::LOCATION_TYPES), $result['totalCount']);
    }
}
