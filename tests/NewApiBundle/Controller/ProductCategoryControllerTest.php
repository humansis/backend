<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use CommonBundle\DataFixtures\VendorFixtures;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\ProductCategory;
use NewApiBundle\Enum\ProductCategoryType;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;
use VoucherBundle\Entity\Vendor;

class ProductCategoryControllerTest extends AbstractFunctionalApiTest
{
    public function testCreate()
    {
        /** @var ProductCategory|null $productCategory */
        $productCategory = self::$container->get('doctrine')->getRepository(ProductCategory::class)->findOneBy(['type' => ProductCategoryType::FOOD], ['id' => 'asc']);

        if (!$productCategory instanceof ProductCategory) {
            $this->markTestSkipped('There needs to be at least one product category in system to complete this test');
        }

        $this->client->request('POST', '/api/basic/web-app/v1/product-categories', $data = [
            'name' => 'Test category',
            'type' => ProductCategoryType::FOOD,
            'image' => 'http://example.org/image.jpg',
        ], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('POST', '/api/basic/web-app/v1/product-categories/'.$id, $data = [
            'name' => 'Another Test category',
            'type' => ProductCategoryType::NONFOOD,
            'image' => 'http://example.org/other-image.jpg',
        ], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/product-categories/'.$id, [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

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
        $this->client->request('GET', '/api/basic/web-app/v1/product-categories?sort[]=name.asc&filter[id][]=1', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

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
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        /** @var Vendor $vendor */
        $vendor = $em->getRepository(Vendor::class)->findOneBy(['name' => VendorFixtures::VENDOR_KHM_NAME], ['id' => 'asc']);
        if (!$vendor) {
            $this->fail('Vendor from SYR missing');
        }
        $foods = $em->getRepository(ProductCategory::class)->findBy(['type' => ProductCategoryType::FOOD], ['id' => 'asc']);
        $nonfoods = $em->getRepository(ProductCategory::class)->findBy(['type' => ProductCategoryType::NONFOOD], ['id' => 'asc']);
        $cashbacks = $em->getRepository(ProductCategory::class)->findBy(['type' => ProductCategoryType::CASHBACK], ['id' => 'asc']);
        if (empty($foods) || empty($nonfoods) || empty($cashbacks)) {
            $this->fail('There are missing categories');
        }
        $vendor->setCanSellFood($canSellFood);
        $vendor->setCanSellNonFood($canSellNonFood);
        $vendor->setCanSellCashback($canSellCashback);
        $em->persist($vendor);
        $em->flush();
        $em->clear();

        $expectedFilteredCategories = 0;
        if ($canSellFood) $expectedFilteredCategories += count($foods);
        if ($canSellNonFood) $expectedFilteredCategories += count($nonfoods);
        if ($canSellCashback) $expectedFilteredCategories += count($cashbacks);

        $this->client->request('GET', '/api/basic/vendor-app/v1/product-categories?sort[]=name.asc&filter[vendors][]='.$vendor->getId(), [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
