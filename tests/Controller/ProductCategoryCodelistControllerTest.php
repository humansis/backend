<?php

declare(strict_types=1);

namespace Tests\Controller;

use Exception;
use Enum\ProductCategoryType;
use Tests\BMSServiceTestCase;

class ProductCategoryCodelistControllerTest extends BMSServiceTestCase
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
    public function testGetTypes()
    {
        $this->request('GET', '/api/basic/web-app/v1/product-categories/types');

        $result = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(ProductCategoryType::values()), $result['totalCount']);
    }
}
