<?php

namespace CommonBundle\DataFixtures;

use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
use CommonBundle\Repository\Adm1Repository;
use CommonBundle\Repository\Adm2Repository;
use CommonBundle\Repository\Adm3Repository;
use CommonBundle\Repository\Adm4Repository;
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

    public function __construct(
        Kernel          $kernel,
        LocationService $locationService,
        Adm1Repository  $adm1Repository,
        Adm2Repository  $adm2Repository,
        Adm3Repository  $adm3Repository,
        Adm4Repository  $adm4Repository
    ) {
        $this->env = $kernel->getEnvironment();
        $this->locationService = $locationService;
        $this->adm1Repository = $adm1Repository;
        $this->adm2Repository = $adm2Repository;
        $this->adm3Repository = $adm3Repository;
        $this->adm4Repository = $adm4Repository;
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
