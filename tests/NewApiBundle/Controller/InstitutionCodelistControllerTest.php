<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Institution;
use Exception;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class InstitutionCodelistControllerTest extends AbstractFunctionalApiTest
{
    /**
     * @throws Exception
     */
    public function testGetLocationTypes()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/institutions/types', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful(), 'Request failed: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(Institution::TYPE_ALL), $result['totalCount']);
    }
}
