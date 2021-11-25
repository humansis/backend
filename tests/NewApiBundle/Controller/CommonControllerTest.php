<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use Exception;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class CommonControllerTest extends AbstractFunctionalApiTest
{
    /**
     * @throws Exception
     */
    public function testGetSummaries()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/summaries?code[]=total_registrations&code[]=active_projects', ['country' => 'KHM'], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "totalCount": 2, 
            "data": [
                {"code": "total_registrations", "value": "*"},
                {"code": "active_projects", "value": "*"}
            ]}', $this->client->getResponse()->getContent());
    }

    /**
     * @throws Exception
     */
    public function testGetIcons()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/icons', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/currencies', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('[
            {"code": "*", "value": "*"}
        ]', $this->client->getResponse()->getContent());
    }

    /**
     * @throws Exception
     */
    public function testGetLanguages()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/languages', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('[
            {"code": "en", "value": "English"}
        ]', $this->client->getResponse()->getContent());
    }

    /**
     * @throws Exception
     */
    public function testGetTranslations()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/translations/en', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{"Assistance": "*"}', $this->client->getResponse()->getContent());
    }

    /**
     * @throws Exception
     */
    public function testGetAdmsNames()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/adms', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "adm1": "*",
            "adm2": "*",
            "adm3": "*",
            "adm4": "*"
        }', $this->client->getResponse()->getContent());
    }
}
