<?php

namespace CommonBundle\DataFixtures;

use NewApiBundle\Entity\Camp;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use ProjectBundle\Entity\Donor;

class DonorFixtures extends Fixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $donor = new Donor();
        $donor->setFullname("Donor full");
        $donor->setShortname("DnrShrt");
        $donor->setDateAdded(new \DateTime());

        $manager->persist($donor);
        $manager->flush();
    }
}
