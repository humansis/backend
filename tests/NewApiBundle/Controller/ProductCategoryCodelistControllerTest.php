<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use Exception;
use NewApiBundle\Enum\ProductCategoryType;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class ProductCategoryCodelistControllerTest extends AbstractFunctionalApiTest
{
    /**
     * @throws Exception
     */
    public function testGetTypes()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/product-categories/types', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(ProductCategoryType::values()), $result['totalCount']);
    }
}


