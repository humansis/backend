<?php

namespace DataFixtures;

use Entity\Camp;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Entity\Location;

class CampFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $location = $manager->getRepository(Location::class)->findBy([], ['id' => 'asc'], 1)[0];

        $camp = (new Camp())
            ->setName('Camp David')
            ->setLocation($location);

        $manager->persist($camp);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [];
    }
}
