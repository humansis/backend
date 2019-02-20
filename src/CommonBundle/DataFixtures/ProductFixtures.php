<?php


namespace CommonBundle\DataFixtures;


use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use VoucherBundle\Entity\Product;


class ProductFixtures extends Fixture
{

    private $data = [
        ['pear', 'KG', '', 0],
        ['rice', 'KG', '', 0],
        ['toothpaste', 'Unit', '', 0],
        ['apple', 'KG', '', 0]
    ];

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $datum)
        {
            $product = new Product();
            $product->setName($datum[0])
                ->setUnit($datum[1])
                ->setImage($datum[2])
                ->setArchived($datum[3]);
            $manager->persist($product);
            $manager->flush();
        }
    }
}