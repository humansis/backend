<?php

namespace Tests\Controller;

use Entity\Beneficiary;
use Entity\HouseholdLocation;
use Entity\NationalId;
use Entity\Phone;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Tests\BMSServiceTestCase;

class CommonControllerTest extends BMSServiceTestCase
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
    public function testGetSummaries()
    {
        $this->request(
            'GET',
            '/api/basic/web-app/v1/summaries?code[]=total_registrations&code[]=active_projects',
            ['country' => 'KHM']
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "totalCount": 2,
            "data": [
                {"code": "total_registrations", "value": "*"},
                {"code": "active_projects", "value": "*"}
            ]}',
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetIcons()
    {
        $this->request('GET', '/api/basic/web-app/v1/icons');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '[{"key": "*", "svg": "*"}]',
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetCurrencies()
    {
        $this->request('GET', '/api/basic/web-app/v1/currencies');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '[
            {"code": "*", "value": "*"}
        ]',
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetLanguages()
    {
        $this->request('GET', '/api/basic/web-app/v1/languages');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '[
            {"code": "en", "value": "English"}
        ]',
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetTranslations()
    {
        $this->request('GET', '/api/basic/web-app/v1/translations/en');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{"Assistance": "*"}', $this->client->getResponse()->getContent());
    }

    /**
     * @throws Exception
     */
    public function testGetAdmsNames()
    {
        $this->request('GET', '/api/basic/web-app/v1/adms');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "adm1": "*",
            "adm2": "*",
            "adm3": "*",
            "adm4": "*"
        }',
            $this->client->getResponse()->getContent()
        );
    }
}
