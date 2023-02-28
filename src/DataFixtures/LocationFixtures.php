<?php

namespace DataFixtures;

use Repository\LocationRepository;
use Services\LocationService;
use Utils\LocationImporter;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Component\Country\Countries;

class LocationFixtures extends Fixture implements FixtureGroupInterface
{
    // maximum imported lines per file (due to performace on dev env)
    final public const LIMIT = 10;

    public function __construct(private readonly LocationRepository $locationRepository, private readonly LocationService $locationService, private readonly Countries $countries)
    {
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        $manager->getConnection()->getConfiguration()->setSQLLogger(null);

        $directory = __DIR__ . '/../Resources/locations';

        $countries = $this->locationService->getADMFiles();
        foreach ($countries as $countryFileUrl) {
            // LOCATION IMPORT
            $importer = new LocationImporter($manager, $countryFileUrl, $this->locationRepository);
            $limit = self::LIMIT;
            echo "FILE PART($limit) IMPORT LOCATION: $countryFileUrl \n";
            $importer->setLimit($limit);
            foreach ($importer->importLocations() as $importStatus) {
                echo '.';
            }
            echo "\n";

            $country = $this->countries->getCountry($importer->getIso3());
            if (!$country || $country->isArchived()) {
                echo 'Skip non-existing or archived country ' . $importer->getIso3();
            }
        }
    }

    /**
     * This method must return an array of groups
     * on which the implementing class belongs to.
     *
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['location'];
    }
}
