<?php

namespace CommonBundle\DataFixtures;

use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
use CommonBundle\Utils\AdmsImporter;
use CommonBundle\Utils\LocationImporter;
use CommonBundle\Utils\LocationService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpKernel\Kernel;

class LocationFixtures extends Fixture implements FixtureGroupInterface
{
    // maximum imported lines per file (due to performace on dev env)
    const LIMIT = 10;

    /** @var string */
    private $env;

    /** @var LocationService */
    private $locationService;

    public function __construct(Kernel $kernel, LocationService $locationService)
    {
        $this->env = $kernel->getEnvironment();
        $this->locationService = $locationService;
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

            $admImported = new AdmsImporter($manager, $filepath);

            $limit = self::LIMIT;
            echo "FILE PART($limit) IMPORT ADMX: $filepath \n";
            $admImported->setLimit($limit);
            foreach ($admImported->importLocations() as $importStatus) {
                echo '.';
            }
            echo "\n";

            $locationImported = new LocationImporter($manager, $filepath);

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
