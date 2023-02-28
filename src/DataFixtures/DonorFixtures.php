<?php

namespace DataFixtures;

use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Entity\Donor;

class DonorFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $donor = new Donor();
        $donor->setFullname("Donor full");
        $donor->setShortname("DnrShrt");
        $donor->setDateAdded(new DateTime());

        $manager->persist($donor);
        $manager->flush();
    }
}
