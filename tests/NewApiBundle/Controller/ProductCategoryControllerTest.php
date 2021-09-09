<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use Exception;
use NewApiBundle\Entity\ProductCategory;
use NewApiBundle\Enum\ProductCategoryType;
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

    public function testCreate()
    {
        /** @var ProductCategory|null $productCategory */
        $productCategory = self::$container->get('doctrine')->getRepository(ProductCategory::class)->findOneBy(['type' => ProductCategoryType::FOOD]);

        if (!$productCategory instanceof ProductCategory) {
            $this->markTestSkipped('There needs to be at least one product category in system to complete this test');
        }

        $this->request('POST', '/api/basic/web-app/v1/product-categories', $data = [
            'name' => 'Test category',
            'type' => ProductCategoryType::FOOD,
            'image' => 'http://example.org/image.jpg',
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('image', $result);

        $this->assertEquals($data['name'], $result['name']);
        $this->assertEquals($data['type'], $result['type']);
        $this->assertEquals($data['image'], $result['image']);

        return $result['id'];
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(int $id)
    {
        $this->request('POST', '/api/basic/web-app/v1/product-categories/'.$id, $data = [
            'name' => 'Another Test category',
            'type' => ProductCategoryType::NONFOOD,
            'image' => 'http://example.org/other-image.jpg',
        ]);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('image', $result);

        $this->assertEquals($data['name'], $result['name']);
        $this->assertEquals($data['type'], $result['type']);
        $this->assertEquals($data['image'], $result['image']);

        return $id;
    }

    /**
     * @depends testCreate
     */
    public function testGet(int $id)
    {
        $this->request('GET', '/api/basic/web-app/v1/product-categories/'.$id);

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('image', $result);
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
            '{"totalCount": "*", "data": [{"id": "*", "name": "*", "type": "*", "image": "*"}]}',
            $this->client->getResponse()->getContent()
        );
    }
}
