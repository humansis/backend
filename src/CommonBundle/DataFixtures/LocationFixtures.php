<?php

namespace CommonBundle\DataFixtures;

use CommonBundle\Repository\Adm1Repository;
use CommonBundle\Repository\Adm2Repository;
use CommonBundle\Repository\Adm3Repository;
use CommonBundle\Repository\Adm4Repository;
use CommonBundle\Repository\LocationRepository;
use CommonBundle\Utils\AdmsImporter;
use CommonBundle\Utils\LocationImporter;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Country\Countries;

class LocationFixtures extends Fixture implements FixtureGroupInterface
{
    // maximum imported lines per file (due to performace on dev env)
    const LIMIT = 10;

    /**
     * @var Adm1Repository
     */
    private $adm1Repository;

    /**
     * @var Adm2Repository
     */
    private $adm2Repository;

    /**
     * @var Adm3Repository
     */
    private $adm3Repository;

    /**
     * @var Adm4Repository
     */
    private $adm4Repository;

    /**
     * @var LocationRepository
     */
    private $locationRepository;

    /**
     * @var Countries
     */
    private $countries;

    public function __construct(
        Adm1Repository     $adm1Repository,
        Adm2Repository     $adm2Repository,
        Adm3Repository     $adm3Repository,
        Adm4Repository     $adm4Repository,
        LocationRepository $locationRepository,
        Countries          $countries
    ) {
        $this->adm1Repository = $adm1Repository;
        $this->adm2Repository = $adm2Repository;
        $this->adm3Repository = $adm3Repository;
        $this->adm4Repository = $adm4Repository;
        $this->locationRepository = $locationRepository;
        $this->countries = $countries;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $manager->getConnection()->getConfiguration()->setSQLLogger(null);

        $directory = __DIR__.'/../Resources/locations';

        foreach (scandir($directory) as $file) {
            if ('.' == $file || '..' == $file) {
                continue;
            }

            $filepath = realpath($directory.'/'.$file);

            $admImported = new AdmsImporter($manager, $filepath, $this->adm1Repository, $this->adm2Repository, $this->adm3Repository,
                $this->adm4Repository);

            $country = $this->countries->getCountry($admImported->getIso3());
            if(!$country || $country->isArchived()){
                echo 'Skip non-existing or archived country ' . $admImported->getIso3();
                continue;
            }

            $limit = self::LIMIT;
            echo "FILE PART($limit) IMPORT ADMX: $filepath \n";
            $admImported->setLimit($limit);
            foreach ($admImported->importLocations() as $importStatus) {
                echo '.';
            }
            echo "\n";

            $locationImported = new LocationImporter($manager, $filepath, $this->locationRepository);

            $limit = self::LIMIT;
            echo "FILE PART($limit) IMPORT LOCATION: $filepath \n";
            $locationImported->setLimit($limit);
            foreach ($locationImported->importLocations() as $importStatus) {
                echo '.';
            }
            echo "\n";
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
