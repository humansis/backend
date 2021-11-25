<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use CommonBundle\DataFixtures\VendorFixtures;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\ProductCategory;
use NewApiBundle\Enum\ProductCategoryType;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;
use VoucherBundle\Entity\Product;
use VoucherBundle\Entity\Vendor;

class ProductControllerTest extends AbstractFunctionalApiTest
{
    public function testCreate()
    {
        /** @var ProductCategory|null $productCategory */
        $productCategory = self::$container->get('doctrine')->getRepository(ProductCategory::class)->findOneBy(['type' => ProductCategoryType::FOOD], ['id' => 'asc']);

        if (!$productCategory instanceof ProductCategory) {
            $this->markTestSkipped('There needs to be at least one product category in system to complete this test');
        }

        $this->client->request('POST', '/api/basic/web-app/v1/products', [
            'name' => 'Test product',
            'unit' => 'Kg',
            'image' => 'http://example.org/image.jpg',
            'iso3' => 'KHM',
            'productCategoryId' => $productCategory->getId(),
        ], [] , $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('unit', $result);
        $this->assertArrayHasKey('image', $result);
        $this->assertArrayHasKey('iso3', $result);
        $this->assertArrayHasKey('productCategoryId', $result);

        $this->assertEquals('Test product', $result['name']);
        $this->assertEquals('Kg', $result['unit']);
        $this->assertEquals('http://example.org/image.jpg', $result['image']);
        $this->assertEquals('KHM', $result['iso3']);
        $this->assertEquals($productCategory->getId(), $result['productCategoryId']);

        return $result['id'];
    }

    public function testCreateCashback()
    {
        /** @var ProductCategory|null $productCategory */
        $productCategory = self::$container->get('doctrine')->getRepository(ProductCategory::class)->findOneBy(['type' => ProductCategoryType::CASHBACK], ['id' => 'asc']);

        if (!$productCategory instanceof ProductCategory) {
            $this->markTestSkipped('There needs to be at least one product category in system to complete this test');
        }

        $this->client->request('POST', '/api/basic/web-app/v1/products', [
            'name' => 'Give money',
            'unit' => null,
            'image' => 'http://example.org/image.jpg',
            'iso3' => 'KHM',
            'productCategoryId' => $productCategory->getId(),
            'unitPrice' => 10.85,
            'currency' => 'USD',
        ], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('unit', $result);
        $this->assertArrayHasKey('image', $result);
        $this->assertArrayHasKey('iso3', $result);
        $this->assertArrayHasKey('productCategoryId', $result);
        $this->assertArrayHasKey('unitPrice', $result);
        $this->assertArrayHasKey('currency', $result);

        $this->assertEquals('Give money', $result['name']);
        $this->assertNull($result['unit']);
        $this->assertEquals('http://example.org/image.jpg', $result['image']);
        $this->assertEquals('KHM', $result['iso3']);
        $this->assertEquals('USD', $result['currency']);
        $this->assertEquals(10.85, $result['unitPrice']);
        $this->assertEquals($productCategory->getId(), $result['productCategoryId']);
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(int $id)
    {
        $this->client->request('PUT', '/api/basic/web-app/v1/products/'.$id, [
            'image' => 'http://example.org/image2.jpg',
        ], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('unit', $result);
        $this->assertArrayHasKey('image', $result);
        $this->assertArrayHasKey('iso3', $result);
        $this->assertArrayHasKey('productCategoryId', $result);
        $this->assertNull($result['unit']);

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testGet(int $id)
    {
        $this->client->request('GET', '/api/basic/web-app/v1/products/'.$id, [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('unit', $result);
        $this->assertArrayHasKey('image', $result);
        $this->assertArrayHasKey('iso3', $result);
        $this->assertArrayHasKey('productCategoryId', $result);
        $this->assertNull($result['unit']);

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testList()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/products?sort[]=name.asc&filter[id][]=1', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function validFilterCombinationsForCategoryTypes(): array
    {
        return [ // food, nonfood, cashback, limit for products in DB
            'no products' => [false, false, false],
            'only food' => [true, false, false],
            'only non-food' => [false, true, false],
            'only cashback' => [false, false, true],
            'without cashback' => [true, true, false],
            'all products' => [true, true, true],
        ];
    }

    /**
     * @dataProvider validFilterCombinationsForCategoryTypes
     */
    public function testListFilteredByVendor(bool $canSellFood, bool $canSellNonFood, bool $canSellCashback)
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        /** @var Vendor $vendor */
        $vendor = $em->getRepository(Vendor::class)->findOneBy(['name' => VendorFixtures::VENDOR_KHM_NAME], ['id' => 'asc']);
        if (!$vendor) {
            $this->fail('Vendor from SYR missing');
        }
        $foods = $em->getRepository(Product::class)->getByCategoryType('KHM', ProductCategoryType::FOOD);
        $nonfoods = $em->getRepository(Product::class)->getByCategoryType('KHM', ProductCategoryType::NONFOOD);
        $cashbacks = $em->getRepository(Product::class)->getByCategoryType('KHM', ProductCategoryType::CASHBACK);
        if (empty($foods) || empty($nonfoods) || empty($cashbacks)) {
            $this->fail('There are missing products');
        }
        $vendor->setCanSellFood($canSellFood);
        $vendor->setCanSellNonFood($canSellNonFood);
        $vendor->setCanSellCashback($canSellCashback);
        $em->persist($vendor);
        $em->flush();
        $em->clear();

        $expectedFilteredProducts = 0;
        if ($canSellFood) $expectedFilteredProducts += count($foods);
        if ($canSellNonFood) $expectedFilteredProducts += count($nonfoods);
        if ($canSellCashback) $expectedFilteredProducts += count($cashbacks);

        $this->client->request('GET', '/api/basic/web-app/v1/products?sort[]=name.asc&filter[vendors][]='.$vendor->getId(), [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertEquals($expectedFilteredProducts, $result['totalCount']);

    }

    /**
     * @depends testGet
     */
    public function testDelete(int $id)
    {
        $this->client->request('DELETE', '/api/basic/web-app/v1/products/'.$id, [], [], $this->addAuth());

        $this->assertTrue($this->client->getResponse()->isEmpty());

        return $id;
    }

    /**
     * @depends testDelete
     */
    public function testGetNotexists(int $id)
    {
        $this->client->request('GET', '/api/basic/web-app/v1/products/'.$id, [], [], $this->addAuth());

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }
}
