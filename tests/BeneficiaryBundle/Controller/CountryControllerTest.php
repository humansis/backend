<?php

namespace Tests\BeneficiaryBundle\Controller;

use BeneficiaryBundle\Entity\CountrySpecific;
use CommonBundle\Utils\ExportService;
use Tests\BMSServiceTestCase;



class CountryControllerTest extends BMSServiceTestCase {

    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("jms_serializer");
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = $this->container->get('test.client');
    }

    public function testGetCountrySpecificsAction() {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $countryResponse = $this->request('GET', 'api/wsse/country_specifics');
        $listCountry = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertArrayHasKey('id', $listCountry[0]);
        $this->assertArrayHasKey('field_string', $listCountry[0]);
        $this->assertArrayHasKey('country_iso3', $listCountry[0]);
        $this->assertArrayHasKey('type', $listCountry[0]);

        return true;
    }

    public function testCreateAction() {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $countryResponse = $this->request('PUT', 'api/wsse/country_specifics');
        $listCountry = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        //

        return true;
    }

    public function testUpdateAction() {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $countryResponse = $this->request('POST', 'api/wsse/country_specifics/{id}');
        $listCountry = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        //
        
        return true;
    }

    public function testDeleteAction() {
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);
        
        $countryResponse = $this->request("DELETE", 'api/wsse/country_specifics/{id}'); 
        $listCountry = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        //

        return true;
    }

}