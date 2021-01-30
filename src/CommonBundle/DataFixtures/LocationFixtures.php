<?php

namespace CommonBundle\DataFixtures;

use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
use CommonBundle\Utils\LocationImporter;
use CommonBundle\Utils\LocationService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;

class LocationFixtures extends Fixture implements FixtureGroupInterface
{
    // maximum imported lines per file (due to performace on dev env)
    const LIMIT = 10;

    /** @var string */
    private $env;

    /** @var LocationService */
    private $locationService;

    public function __construct(KernelInterface $kernel, LocationService $locationService)
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

            $admImported = new LocationImporter($manager, $filepath);

            $limit = self::LIMIT;
            echo "FILE PART($limit) IMPORT : $filepath \n";
            $admImported->setLimit($limit);
            foreach ($admImported->importLocations() as $importStatus) {
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
