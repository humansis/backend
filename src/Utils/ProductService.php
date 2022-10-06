<?php

namespace Utils;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Entity\ProductCategory;
use Enum\ProductCategoryType;
use InputType\ProductCreateInputType;
use InputType\ProductUpdateInputType;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Entity\Product;
use Psr\Container\ContainerInterface;

class ProductService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * UserService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
    }

    /**
     * Creates a new Product entity.
     *
     * @param ProductCreateInputType $productData
     *
     * @return Product
     * @throws EntityNotFoundException
     */
    public function create(ProductCreateInputType $productData)
    {
        $product = (new Product())
            ->setName($productData->getName())
            ->setImage($productData->getImage())
            ->setUnit($productData->getUnit())
            ->setCountryIso3($productData->getIso3())
            ->setUnitPrice($productData->getUnitPrice())
            ->setCurrency($productData->getCurrency())
            ->setArchived(false);

        if (null !== $productData->getProductCategoryId()) {
            /** @var ProductCategory|null $productCategory */
            $productCategory = $this->em->getRepository(ProductCategory::class)->find($productData->getProductCategoryId());

            if (!$productCategory instanceof ProductCategory) {
                throw new EntityNotFoundException('ProductCategory with ID ' . $productData->getProductCategoryId() . ' not found');
            }

            if (ProductCategoryType::CASHBACK === $productCategory->getType() && (empty($product->getUnitPrice()) || empty($product->getCurrency()))) {
                throw new BadRequestHttpException("Cashback must have unitPrice and currency");
            }

            $product->setProductCategory($productCategory);
        } else {
            $product->setProductCategory(null);
        }

        $this->em->persist($product);
        $this->em->flush();

        return $product;
    }

    /**
     * Updates a product according to the $productData.
     *
     * @param Product $product
     * @param ProductUpdateInputType $productData
     *
     * @return Product
     * @throws EntityNotFoundException
     */
    public function update(Product $product, ProductUpdateInputType $productData)
    {
        $product
            ->setUnit($productData->getUnit())
            ->setImage($productData->getImage())
            ->setUnitPrice($productData->getUnitPrice())
            ->setCurrency($productData->getCurrency());

        if (null !== $productData->getProductCategoryId()) {
            /** @var ProductCategory|null $productCategory */
            $productCategory = $this->em->getRepository(ProductCategory::class)->find($productData->getProductCategoryId());

            if (!$productCategory instanceof ProductCategory) {
                throw new EntityNotFoundException('ProductCategory with ID ' . $productData->getProductCategoryId() . ' not found');
            }

            $product->setProductCategory($productCategory);
        } else {
            $product->setProductCategory(null);
        }

        $this->em->persist($product);
        $this->em->flush();

        return $product;
    }

    /**
     * Archives a product
     *
     * @param Product $product
     * @return string
     */
    public function archive(Product $product)
    {
        $product->setArchived(true);

        $this->em->persist($product);
        $this->em->flush();

        return "Product suppressed";
    }

    /**
     * Export all products in a CSV file
     *
     * @param string $type
     * @param string $countryIso3
     * @return mixed
     */
    public function exportToCsv(string $type, string $countryIso3)
    {
        $exportableTable = $this->em->getRepository(Product::class)->findBy(['archived' => false, 'countryIso3' => $countryIso3]);

        return $this->container->get('export_csv_service')->export($exportableTable, 'products', $type);
    }
}
