<?php


namespace CommonBundle\DataFixtures;


use CommonBundle\Entity\Location;
use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class LocationUnitTestFixtures extends Fixture
{

    private $data = [
        ['KHM', 'Rhone-Alpes', 'Savoie', 'Chambery', 'Sainte Hélène sur Isère'],
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
            $adm1 = new Adm1();
            $adm1->setCountryISO3($datum[0])
                ->setName($datum[1]);
            $manager->persist($adm1);

            $adm2 = new Adm2();
            $adm2->setAdm1($adm1)
                ->setName($datum[2]);
            $manager->persist($adm2);

            $adm3 = new Adm3();
            $adm3->setAdm2($adm2)
                ->setName($datum[3]);
            $manager->persist($adm3);

            $adm4 = new Adm4();
            $adm4->setAdm3($adm3)
                ->setName($datum[4]);
            $manager->persist($adm4);

            $manager->flush();
        }
    }
}