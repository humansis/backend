<?php

namespace DataFixtures;

use Repository\CountrySpecificRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Entity\CountrySpecific;
use Component\Country\Countries;

class CountrySpecificFixtures extends Fixture
{
    private array $data = [
        ['IDPoor', 'number'],
        ['equityCardNo', 'text'],
        ['CSO float property', 'number'],
    ];

    public function __construct(private readonly Countries $countries, private readonly CountrySpecificRepository $countrySpecificRepository)
    {
    }

    /**
     * Load data fixtures with the passed EntityManager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->countries->getAll() as $country) {
            foreach ($this->data as $cso) {
                $countrySpecific = $this->countrySpecificRepository->findOneBy([
                    "fieldString" => $cso[0],
                    "type" => $cso[1],
                    "countryIso3" => $country->getIso3(),
                ], ['id' => 'asc']);
                if (!$countrySpecific instanceof CountrySpecific) {
                    $countrySpecific = new CountrySpecific($cso[0], $cso[1], $country->getIso3());
                    $manager->persist($countrySpecific);
                    $manager->flush();
                }
            }
        }
    }
}
