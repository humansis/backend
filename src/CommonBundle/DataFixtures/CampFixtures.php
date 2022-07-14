<?php

namespace CommonBundle\DataFixtures;

use NewApiBundle\Entity\Camp;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CampFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $location = $manager->getRepository(\CommonBundle\Entity\Location::class)->findBy([], ['id' => 'asc'], 1)[0];

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
