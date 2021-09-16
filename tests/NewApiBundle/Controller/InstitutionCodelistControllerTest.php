<?php

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Institution;
use Exception;
use Tests\BMSServiceTestCase;

class InstitutionCodelistControllerTest extends BMSServiceTestCase
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
    public function testGetLocationTypes()
    {
        $this->request('GET', '/api/basic/web-app/v1/institutions/types');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(Institution::TYPE_ALL), $result['totalCount']);
    }
}
