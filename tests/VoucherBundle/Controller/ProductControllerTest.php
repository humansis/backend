<?php
namespace VoucherBundle\Tests\Controller;

use Tests\BMSServiceTestCase;
use VoucherBundle\Entity\Product;

class ProductControllerTest extends BMSServiceTestCase
{
    /**
     * @throws \Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName("jms_serializer");
        parent::setUpFunctionnal();
        // Get a Client instance for simulate a browser
        $this->client = $this->container->get('test.client');


    }

    /**
     * @throws \Exception
     */
    public function testCreateProduct()
    {
        $body = [
            "image" => 'image.png',
            "name" => 'test',
            "unit" => 'KG'
        ];

        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        $crawler = $this->request('PUT', '/api/wsse/products', $body);
        $product = json_decode($this->client->getResponse()->getContent(), true);
        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertArrayHasKey('image', $product);
        $this->assertArrayHasKey('name', $product);
        $this->assertArrayHasKey('unit', $product);

        return $product;
    }

    /**
     * @throws \Exception
     */
    public function testGetAllProducts()
    {
        // Log a user in order to go through the security firewall
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('GET', '/api/wsse/products');
        $products = json_decode($this->client->getResponse()->getContent(), true);

        if (!empty($products)) {
            $product = $products[0];

            $this->assertArrayHasKey('image', $product);
            $this->assertArrayHasKey('name', $product);
            $this->assertArrayHasKey('unit', $product);
        } else {
            $this->markTestIncomplete("You currently don't have any products in your database.");
        }

        return $products[0];
    }

    
    /**
     * @depends testCreateProduct
     * @param $newProduct
     * @return mixed
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testEditProduct($newProduct)
    {
        $unit = "Centiliters";
        $body = ["image" => 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a170aba331.png', "unit" => $unit];

        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        $crawler = $this->request('POST', '/api/wsse/products/' . $newProduct['id'], $body);
        $newProductReceived = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $productSearch = $this->em->getRepository(Product::class)->find($newProductReceived['id']);
        $this->assertEquals($productSearch->getUnit(), $unit);

        return $newProductReceived;
    }


    /**
     * @depends testDeleteProduct
     *
     * @param $productToDelete
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testDeleteFromDatabase($productToDelete)
    {
        // Fake connection with a token for the user tester (ADMIN)
        $user = $this->getTestUser(self::USER_TESTER);
        $token = $this->getUserToken($user);
        $this->tokenStorage->setToken($token);

        // Second step
        // Create the user with the email and the salted password. The user should be enable
        $crawler = $this->request('DELETE', '/api/wsse/products/' . $productToDelete['id']);
        $success = json_decode($this->client->getResponse()->getContent(), true);

        // Check if the second step succeed
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        return $success;
    }
}