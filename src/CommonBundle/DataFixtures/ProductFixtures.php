<?php


namespace CommonBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Entity\ProductCategory;
use VoucherBundle\Entity\Product;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    private $data = [
        ['soap', 'Unit', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a1505596a4.jpg', 0, 2, 'SYR'],
        ['toothbrush', 'Unit', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a15252e30e.jpg', 0, 2],
        ['pear', 'KG', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a158059d28.jpg', 0, 1],
        ['rice', 'KG', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a154759a4d.jpg', 0, 1],
        ['flour', 'KG', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a159580fdf.jpg', 0,  1],
        ['toothpaste', 'Unit', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a15a54ae7f.jpg', 0, 2, 'SYR'],
        ['apple', 'KG', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a15d379307.jpeg', 0, 1, 'SYR'],
        ['cherry', 'KG', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a160605fe0.jpg', 0, 1],
        ['book', 'Unit', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a161a9d3dd.png', 0, 2],
        ['cake', 'Unit', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/products/5c8a162f9cdeb.jpg', 0, 1],

    ];

    private $countries = [];

    public function __construct(array $countries)
    {
        $this->countries = [];
        foreach ($countries as $country) {
            $this->countries[$country['iso3']] = $country;
        }
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $datum) {
            $product = new Product();
            $product->setName($datum[0])
                ->setUnit($datum[1])
                ->setImage($datum[2])
                ->setArchived($datum[3])
                ->setProductCategory($this->findCategory($manager, $datum[4]))
                ;

            if (isset($datum[5]) & !empty($datum[5])) {
                $product->setCountryISO3($datum[5]);
                $manager->persist($product);
            } else {
                foreach ($this->countries as $country) {
                    $p = clone $product;
                    $p->setCountryISO3($country['iso3']);
                    $manager->persist($p);
                }
            }

            $manager->flush();
        }
    }


    /**
     * @param ObjectManager $manager
     * @param int           $id
     *
     * @return ProductCategory
     */
    private function findCategory(ObjectManager $manager, int $id)
    {
        /** @var ProductCategory $productCategory */
        $productCategory = $manager->getRepository(ProductCategory::class)->find($id);

        return $productCategory;
    }

    public function getDependencies(): array
    {
        return [
            ProductCategoryFixtures::class,
        ];
    }
}
