<?php


namespace CommonBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

use ProjectBundle\Entity\Sector;

class SectorFixtures extends Fixture
{
    private $data = [
        ['camp coordination and management'],
        ['early recovery'],
        ['education'],
        ['emergency telecommunications'],
        ['food security'],
        ['health'],
        ['logistics'],
        ['nutrition'],
        ['protection'],
        ['shelter'],
        ['cash for work'],
        ['TVET'],
        ['food, RTE kits'],
        ['NFIs'],
        ['WASH'],
    ];

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $datum) {
            $sector = $manager->getRepository(Sector::class)->findOneBy([
                "name" => $datum[0]
            ]);
            if (!$sector instanceof Sector) {
                $sector = new Sector();
                $sector->setName($datum[0]);
                $manager->persist($sector);
                $manager->flush();
            }
        }
    }
}
