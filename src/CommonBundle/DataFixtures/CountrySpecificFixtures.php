<?php


namespace CommonBundle\DataFixtures;


use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

use BeneficiaryBundle\Entity\CountrySpecific;

class CountrySpecificFixtures extends Fixture
{

    private $data = [
        ['ID Poor', 'Number', 'KHM'],
        ['WASH', 'Text', 'KHM']
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
            $countrySpecific = $manager->getRepository(CountrySpecific::class)->findBy([
                "fieldString" => $datum[0],
                "type" => $datum[1],
                "countryIso3" => $datum[2]
            ]);
            if (!$countrySpecific instanceof CountrySpecific)
            {
                $countrySpecific = new CountrySpecific($datum[0], $datum[1], $datum[2]);
                $manager->persist($countrySpecific);
                $manager->flush();
            }
        }
    }
}