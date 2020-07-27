<?php

namespace CommonBundle\DataFixtures;

use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpKernel\Kernel;

class LocationFixtures extends Fixture implements FixtureGroupInterface
{
    // maximum imported lines per file (due to performace on dev env)
    const LIMIT = 10;

    // array keys for each level of adm
    const ADM_NAME_0 = 0;
    const ADM_NAME_1 = 2;
    const ADM_NAME_2 = 4;
    const ADM_NAME_3 = 6;
    const ADM_NAME_4 = 8;

    const ADM_CODE_0 = 1;
    const ADM_CODE_1 = 3;
    const ADM_CODE_2 = 5;
    const ADM_CODE_3 = 7;
    const ADM_CODE_4 = 9;

    /** @var string */
    private $env;

    public function __construct(Kernel $kernel)
    {
        $this->env = $kernel->getEnvironment();
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $manager->getConnection()->getConfiguration()->setSQLLogger(null);

        if ('prod' !== $this->env) {
            $limit = self::LIMIT;
        } else {
            $limit = 0;
        }

        $directory = __DIR__.'/../Resources/locations';

        foreach (scandir($directory) as $file) {
            if ('.' == $file || '..' == $file) {
                continue;
            }

            $filepath = realpath($directory.'/'.$file);

            echo "FILE : $filepath \n";
            $this->processFile($filepath, $manager, $limit);
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

    protected function processFile(string $file, ObjectManager $manager, int $limit = 0)
    {
        $adm1List = [];
        $adm2List = [];
        $adm3List = [];

        $iso3 = strtoupper(pathinfo($file, PATHINFO_FILENAME));

        $handler = fopen($file, 'r');
        if (false === $handler) {
            throw new FileNotFoundException('File '.$file.' does not exists.');
        }

        // skip first line, eg. header
        $item = fgetcsv($handler);

        $i = 0;
        while (false !== ($item = fgetcsv($handler))) {
            if ($limit > 0 && $i > $limit) {
                continue;
            }

            if (!array_key_exists($item[self::ADM_NAME_1], $adm1List)) {
                $adm1 = $manager->getRepository(Adm1::class)->findOneByCode($item[self::ADM_CODE_1]);
                if (!$adm1) {
                    $adm1 = (new Adm1())
                        ->setCountryISO3($iso3)
                        ->setName(trim($item[self::ADM_NAME_1]))
                        ->setCode($item[self::ADM_CODE_1]);

                    $manager->persist($adm1);
                }

                $adm1List[$item[self::ADM_NAME_1]] = $adm1;
            }

            if (!isset($item[self::ADM_NAME_2])) {
                continue;
            }

            if (!array_key_exists($item[self::ADM_NAME_2], $adm2List)) {
                $adm2 = $manager->getRepository(Adm2::class)->findOneByCode($item[self::ADM_CODE_2]);
                if (!$adm2) {
                    $adm2 = (new Adm2())
                        ->setName(trim($item[self::ADM_NAME_2]))
                        ->setAdm1($adm1List[$item[self::ADM_NAME_1]])
                        ->setCode($item[self::ADM_CODE_2]);

                    $manager->persist($adm2);
                }
                $adm2List[$item[self::ADM_NAME_2]] = $adm2;
            }

            if (!isset($item[self::ADM_NAME_3])) {
                continue;
            }

            if (!array_key_exists($item[self::ADM_NAME_3], $adm3List)) {
                $adm3 = $manager->getRepository(Adm3::class)->findOneByCode($item[self::ADM_CODE_3]);
                if (!$adm3) {
                    $adm3 = (new Adm3())
                        ->setName(trim($item[self::ADM_NAME_3]))
                        ->setAdm2($adm2List[$item[self::ADM_NAME_2]])
                        ->setCode($item[self::ADM_CODE_3]);

                    $manager->persist($adm3);
                }
                $adm3List[$item[self::ADM_NAME_3]] = $adm3;
            }

            if (!isset($item[self::ADM_NAME_4])) {
                continue;
            }

            $adm4 = $manager->getRepository(Adm4::class)->findOneByCode($item[self::ADM_CODE_4]);
            if (!$adm4) {
                $adm4 = (new Adm4())
                    ->setName(trim($item[self::ADM_NAME_4]))
                    ->setCode($item[self::ADM_CODE_4])
                    ->setAdm3($adm3List[$item[self::ADM_NAME_3]]);
                $manager->persist($adm4);
            }

            if (0 === (++$i % 1000)) {
                $manager->flush();
                $manager->clear();
            }
        }

        $manager->flush();
        $manager->clear();

        fclose($handler);
    }
}
