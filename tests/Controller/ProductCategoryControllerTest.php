<?php
declare(strict_types=1);

namespace Tests\Controller;

use DataFixtures\VendorFixtures;
use Exception;
use Entity\ProductCategory;
use Enum\ProductCategoryType;
use Tests\BMSServiceTestCase;
use Entity\Product;
use Entity\Vendor;

class ProductCategoryControllerTest extends BMSServiceTestCase
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
        $this->client = self::$container->get('test.client');
    }

    public function testCreate()
    {
        /** @var ProductCategory|null $productCategory */
        $productCategory = self::$container->get('doctrine')->getRepository(ProductCategory::class)->findOneBy(['type' => ProductCategoryType::FOOD], ['id' => 'asc']);

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
        $this->request('GET', '/api/basic/web-app/v1/product-categories?sort[]=name.asc&filter[id][]=1');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function validFilterCombinationsForCategoryTypes(): array
    {
        return [ // food, nonfood, cashback, limit for products in DB
            'no categories' => [false, false, false],
            'only food' => [true, false, false],
            'only non-food' => [false, true, false],
            'only cashback' => [false, false, true],
            'without cashback' => [true, true, false],
            'all categories' => [true, true, true],
        ];
    }

    /**
     * @dataProvider validFilterCombinationsForCategoryTypes
     */
    public function testListFilteredByVendor(bool $canSellFood, bool $canSellNonFood, bool $canSellCashback)
    {
        /** @var Vendor $vendor */
        $vendor = $this->em->getRepository(Vendor::class)->findOneBy(['name' => VendorFixtures::VENDOR_KHM_NAME], ['id' => 'asc']);
        if (!$vendor) {
            $this->fail('Vendor from SYR missing');
        }
        $foods = $this->em->getRepository(ProductCategory::class)->findBy(['type' => ProductCategoryType::FOOD], ['id' => 'asc']);
        $nonfoods = $this->em->getRepository(ProductCategory::class)->findBy(['type' => ProductCategoryType::NONFOOD], ['id' => 'asc']);
        $cashbacks = $this->em->getRepository(ProductCategory::class)->findBy(['type' => ProductCategoryType::CASHBACK], ['id' => 'asc']);
        if (empty($foods) || empty($nonfoods) || empty($cashbacks)) {
            $this->fail('There are missing categories');
        }
        $vendor->setCanSellFood($canSellFood);
        $vendor->setCanSellNonFood($canSellNonFood);
        $vendor->setCanSellCashback($canSellCashback);
        $this->em->persist($vendor);
        $this->em->flush();
        $this->em->clear();

        $expectedFilteredCategories = 0;
        if ($canSellFood) $expectedFilteredCategories += count($foods);
        if ($canSellNonFood) $expectedFilteredCategories += count($nonfoods);
        if ($canSellCashback) $expectedFilteredCategories += count($cashbacks);

        $this->request('GET', '/api/basic/vendor-app/v1/product-categories?sort[]=name.asc&filter[vendors][]='.$vendor->getId());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertEquals($expectedFilteredCategories, $result['totalCount']);

    }

    public function testListFilteredByNonExistentVendor()
    {
        $this->request('GET', '/api/basic/vendor-app/v1/product-categories?sort[]=name.asc&filter[vendors][]=0');

        $this->assertTrue(
            $this->client->getResponse()->isClientError(),
            'Request should failed by NotFound error: '.$this->client->getResponse()->getContent()
        );
    }
}
