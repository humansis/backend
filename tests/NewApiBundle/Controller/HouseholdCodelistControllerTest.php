<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\HouseholdLocation;
use Exception;
use ProjectBundle\Enum\Livelihood;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class HouseholdCodelistControllerTest extends AbstractFunctionalApiTest
{
    /**
     * @throws Exception
     */
    public function testGetLivelihoods()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/households/livelihoods', [], [], $this->addAuth());

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
        $this->client->request('GET', '/api/basic/web-app/v1/households/assets', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(Household::ASSETS), $result['totalCount']);
    }

    /**
     * @throws Exception
     */
    public function testGetSupportReceivedTypes()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/households/support-received-types', [], [], $this->addAuth());

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
        $this->client->request('GET', '/api/basic/web-app/v1/households/shelter-statuses', [], [], $this->addAuth());

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
        $this->client->request('GET', '/api/basic/web-app/v1/households/locations/types', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(HouseholdLocation::LOCATION_TYPES), $result['totalCount']);
    }
}
