<?php
declare(strict_types=1);

namespace CommonBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use NewApiBundle\DBAL\ProductCategoryTypeEnum;
use NewApiBundle\Entity\ProductCategory;
use NewApiBundle\Enum\ProductCategoryType;

class ProductCategoryFixtures extends Fixture
{
    private const DATA = [
        ['Food (ProductCategoryFixtures)', ProductCategoryType::FOOD],
        ['Non-food (ProductCategoryFixtures)', ProductCategoryType::NONFOOD],
        ['Cashback (ProductCategoryFixtures)', ProductCategoryType::CASHBACK],
    ];

    public function load(ObjectManager $manager)
    {
        foreach (self::DATA as $row) {
            $productCategory = new ProductCategory($row[0], $row[1]);

            $manager->persist($productCategory);
        }

        $manager->flush();
    }
}
