<?php

namespace Tests\BeneficiaryBundle\Controller;

use BeneficiaryBundle\Entity\CountrySpecific;
use CommonBundle\Utils\ExportService;
use Tests\BMSServiceTestCase;

class CountrySpecificControllerTest extends BMSServiceTestCase
{
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("serializer");
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testCreateAction()
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body;
        $body['countryIso3'] = "";
        $body['field'] = "test1";
        $body['name'] = "";
        $body['type'] = "text";

        $countryResponse = $this->request('PUT', 'api/wsse/country_specifics', $body);
        $listCountry = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('id', $listCountry);
        $this->assertArrayHasKey('field_string', $listCountry);
        $this->assertArrayHasKey('country_iso3', $listCountry);
        $this->assertArrayHasKey('type', $listCountry);

        return $listCountry;
    }

    public function testGetCountrySpecificsAction()
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $countryResponse = $this->request('GET', 'api/wsse/country_specifics');
        $listCountry = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('id', $listCountry[0]);
        $this->assertArrayHasKey('field_string', $listCountry[0]);
        $this->assertArrayHasKey('country_iso3', $listCountry[0]);
        $this->assertArrayHasKey('type', $listCountry[0]);

        return true;
    }

    /**
     * @depends testCreateAction
     * @param $objectCountry
     * @return void
     */
    public function testUpdateAction($objectCountry)
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $body;
        $body['countryIso3'] = "";
        $body['field'] = "test2";
        $body['name'] = "";
        $body['type'] = "text";

        $countryResponse = $this->request('POST', 'api/wsse/country_specifics/' . $objectCountry['id'], $body);
        $listCountry = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $this->assertArrayHasKey('id', $listCountry);
        $this->assertArrayHasKey('field_string', $listCountry);
        $this->assertArrayHasKey('country_iso3', $listCountry);
        $this->assertArrayHasKey('type', $listCountry);
        
        return true;
    }

    /**
     * @depends testCreateAction
     * @param $objectCountry
     * @return void
     */
    public function testDeleteAction($objectCountry)
    {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);
        
        $countryResponse = $this->request("DELETE", 'api/wsse/country_specifics/' . $objectCountry['id']);
        $listCountry = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());

        return true;
    }
}
