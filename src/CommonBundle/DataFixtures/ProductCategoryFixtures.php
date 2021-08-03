<?php
declare(strict_types=1);

namespace CommonBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Entity\ProductCategory;

class ProductCategoryFixtures extends Fixture
{
    private const DATA = [
        ['Food'],
        ['Non-food'],
        ['Cashback'],
    ];

    public function load(ObjectManager $manager)
    {
        foreach (self::DATA as $row) {
            $productCategory = new ProductCategory($row[0]);

            $manager->persist($productCategory);
        }

        $manager->flush();
    }
}
