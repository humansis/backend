<?php

namespace DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Entity\Organization;

class OrganizationFixtures extends Fixture
{
    private $data = [
        ['YourOrganization', null, 'Arial', '#02617F', '#4AA896', 'What should be displayed in your pdf\'s footer']
    ];

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $datum) {
            $organization = new Organization();
            $organization->setName($datum[0])
                ->setLogo($datum[1])
                ->setFont($datum[2])
                ->setPrimaryColor($datum[3])
                ->setSecondaryColor($datum[4])
                ->setFooterContent($datum[5]);
            $manager->persist($organization);
            $manager->flush();
        }
    }
}
