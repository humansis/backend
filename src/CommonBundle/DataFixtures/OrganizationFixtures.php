<?php

namespace CommonBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use CommonBundle\Entity\Organization;

class OrganizationFixtures extends Fixture
{
    private $data = [
        ['YourOrganization', 'https://s3.eu-central-1.amazonaws.com/files-testing.bmstaging.info/organization/5ce2d269dfd0a.png', 'Arial', '#02617F', '#4AA896', 'What should be displayed in your pdf\'s footer']
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