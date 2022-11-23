<?php

namespace Tests\Controller;

use Entity\Household;
use Entity\HouseholdLocation;
use Exception;
use Enum\HouseholdAssets;
use Enum\HouseholdShelterStatus;
use Enum\HouseholdSupportReceivedType;
use Enum\Livelihood;
use Tests\BMSServiceTestCase;

class HouseholdCodelistControllerTest extends BMSServiceTestCase
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
    public function testGetLivelihoods()
    {
        $this->request('GET', '/api/basic/web-app/v1/households/livelihoods');

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(is_countable(Livelihood::values()) ? count(Livelihood::values()) : 0, $result['totalCount']);
    }

    /**
     * @throws Exception
     */
    public function testGetAssets()
    {
        $this->request('GET', '/api/basic/web-app/v1/households/assets');

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(HouseholdAssets::values()), $result['totalCount']);
    }

    /**
     * @throws Exception
     */
    public function testGetSupportReceivedTypes()
    {
        $this->request('GET', '/api/basic/web-app/v1/households/support-received-types');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "totalCount": ' . count(HouseholdSupportReceivedType::values()) . ',
            "data": "*"
        }',
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetShelterStatuses()
    {
        $this->request('GET', '/api/basic/web-app/v1/households/shelter-statuses');

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(HouseholdShelterStatus::values()), $result['totalCount']);
    }

    /**
     * @throws Exception
     */
    public function testGetLocationTypes()
    {
        $this->request('GET', '/api/basic/web-app/v1/households/locations/types');

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(HouseholdLocation::LOCATION_TYPES), $result['totalCount']);
    }
}
