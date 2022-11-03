<?php

namespace Tests\Controller;

use Exception;
use Tests\BMSServiceTestCase;

class AclControllerTest extends BMSServiceTestCase
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

    public function testDetail()
    {
        $this->request('GET', '/api/basic/web-app/v1/acl/roles/ROLE_ADMIN');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "name": "Admin",
            "code": "ROLE_ADMIN",
            "privileges": "*"
        }',
            $this->client->getResponse()->getContent()
        );
    }

    public function testList()
    {
        $this->request('GET', '/api/basic/web-app/v1/acl/roles');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "totalCount": "*",
            "data": [
                "*"
             ]
        }',
            $this->client->getResponse()->getContent()
        );
    }
}
