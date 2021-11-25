<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class AclControllerTest extends AbstractFunctionalApiTest
{
    public function testDetail()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/acl/roles/ROLE_ADMIN', [], [], $this->addAuth());
        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

        $this->assertJsonFragment('{
            "name": "Admin",
            "code": "ROLE_ADMIN",
            "privileges": "*"
        }', $this->client->getResponse()->getContent());
    }

    public function testList()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/acl/roles', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": [
                "*"
             ]
        }', $this->client->getResponse()->getContent());
    }
}
