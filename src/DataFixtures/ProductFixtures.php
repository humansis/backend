<?php

namespace DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Component\Country\Countries;
use Entity\ProductCategory;
use Enum\ProductCategoryType;
use Entity\Product;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    private array $data = [
        [
            'soap',
            'Unit',
            'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a1505596a4.jpg',
            0,
            ProductCategoryType::FOOD,
            'SYR',
        ],
        [
            'toothbrush',
            'Unit',
            'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a15252e30e.jpg',
            0,
            ProductCategoryType::NONFOOD,
        ],
        [
            'pear',
            'KG',
            'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a158059d28.jpg',
            0,
            ProductCategoryType::FOOD,
        ],
        [
            'rice',
            'KG',
            'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a154759a4d.jpg',
            0,
            ProductCategoryType::FOOD,
        ],
        [
            'flour',
            'KG',
            'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a159580fdf.jpg',
            0,
            ProductCategoryType::FOOD,
        ],
        [
            'toothpaste',
            'Unit',
            'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a15a54ae7f.jpg',
            0,
            ProductCategoryType::NONFOOD,
            'SYR',
        ],
        [
            'apple',
            'KG',
            'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a15d379307.jpeg',
            0,
            ProductCategoryType::FOOD,
            'SYR',
        ],
        [
            'cherry',
            'KG',
            'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a160605fe0.jpg',
            0,
            ProductCategoryType::FOOD,
        ],
        [
            'book',
            'Unit',
            'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a161a9d3dd.png',
            0,
            ProductCategoryType::NONFOOD,
        ],
        [
            'cake',
            'Unit',
            'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a162f9cdeb.jpg',
            0,
            ProductCategoryType::FOOD,
        ],
        [
            'cashback',
            'Unit',
            'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a162f9cdeb.jpg',
            0,
            ProductCategoryType::CASHBACK,
        ],
        [
            'REMOVED!!!',
            'Unit',
            'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a162f9cdeb.jpg',
            1,
            ProductCategoryType::FOOD,
        ],

    ];

    public function __construct(private readonly Countries $countries)
    {
    }

    /**
     * Load data fixtures with the passed EntityManager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $datum) {
            $product = new Product();
            $product->setName($datum[0])
                ->setUnit($datum[1])
                ->setImage($datum[2])
                ->setArchived($datum[3])
                ->setProductCategory($this->findCategory($manager, $datum[4]));

            if ($datum[4] == ProductCategoryType::CASHBACK) {
                $product->setCurrency('CZK');
                $product->setUnitPrice(10.24);
            }

            if (isset($datum[5]) & !empty($datum[5])) {
                $product->setCountryIso3($datum[5]);
                $manager->persist($product);
            } else {
                foreach ($this->countries->getAll() as $country) {
                    $p = clone $product;
                    $p->setCountryIso3($country->getIso3());
                    $manager->persist($p);
                }
            }

            $manager->flush();
        }
    }

    /**
     *
     * @return ProductCategory
     */
    private function findCategory(ObjectManager $manager, string $type)
    {
        /** @var ProductCategory $productCategory */
        $productCategory = $manager->getRepository(ProductCategory::class)->findOneByType($type);

        return $productCategory;
    }

    public function getDependencies(): array
    {
        return [
            ProductCategoryFixtures::class,
        ];
    }
}
