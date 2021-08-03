<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use Exception;
use NewApiBundle\Entity\ProductCategory;
use Tests\BMSServiceTestCase;

class ProductCategoryControllerTest extends BMSServiceTestCase
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

    public function testGet()
    {
        /** @var ProductCategory|null $productCategory */
        $productCategory = self::$container->get('doctrine')->getRepository(ProductCategory::class)->findOneBy([]);

        if (!$productCategory instanceof ProductCategory) {
            $this->markTestSkipped('There needs to be at least one product category in system to complete this test');
        }

        $this->request('GET', '/api/basic/web-app/v1/product-categories/'.$productCategory->getId());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
    }

    /**
     * @depends testGet
     */
    public function testList()
    {
        $this->request('GET', '/api/basic/web-app/v1/product-categories');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertJsonFragment(
            '{"totalCount": "*", "data": [{"id": "*", "name": "*"}]}',
            $this->client->getResponse()->getContent()
        );
    }
}
