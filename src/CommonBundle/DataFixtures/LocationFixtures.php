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

        $directory = __DIR__.'/../Resources/locations';

        foreach (scandir($directory) as $file) {
            if ('.' == $file || '..' == $file) {
                continue;
            }

            $filepath = realpath($directory.'/'.$file);

            echo "FILE : $filepath \n";
            $this->processFile($filepath, $manager);
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

    protected function processFile(string $file, ObjectManager $manager)
    {
        $iso3 = strtoupper(pathinfo($file, PATHINFO_FILENAME));

        $xml = new \XMLReader();
        if (false === $xml->open($file)) {
            throw new FileNotFoundException('File '.$file.' does not exists.');
        }

        $i = 0;
        $adm1 = $adm2 = $adm3 = $adm4 = null;
        while ($xml->read()) {
            if (\XMLReader::END_ELEMENT === $xml->nodeType && 'adm1' === $xml->name) {
                $manager->flush();
                $manager->clear();
                continue;
            }

            if (\XMLReader::ELEMENT !== $xml->nodeType) {
                continue;
            }

            $name = $xml->getAttribute('name');
            $code = $xml->getAttribute('code');
            if ('adm1' === $xml->name) {
                $adm1 = $this->buildAdm1($name, $code, $iso3, $manager);
            } elseif ('adm2' === $xml->name) {
                $adm2 = $this->buildAdm2($name, $code, $adm1, $manager);
            } elseif ('adm3' === $xml->name) {
                $adm3 = $this->buildAdm3($name, $code, $adm2, $manager);
            } elseif ('adm4' === $xml->name) {
                $this->buildAdm4($name, $code, $adm3, $manager);
            }
        }

        $manager->flush();
        $manager->clear();

        $xml->close();
    }

    private function buildAdm1(string $name, string $code, string $iso3, ObjectManager $manager): Adm1
    {
        $adm1 = $manager->getRepository(Adm1::class)->findOneByCode($code);
        if (!$adm1) {
            $adm1 = (new Adm1())
                ->setCountryISO3($iso3)
                ->setName($name)
                ->setCode($code);

            $manager->persist($adm1);
        }

        return $adm1;
    }

    private function buildAdm2(string $name, string $code, Adm1 $adm1, ObjectManager $manager): Adm2
    {
        $adm2 = $manager->getRepository(Adm2::class)->findOneByCode($code);
        if (!$adm2) {
            $adm2 = (new Adm2())
                ->setAdm1($adm1)
                ->setName($name)
                ->setCode($code);

            $manager->persist($adm2);
        }

        return $adm2;
    }

    private function buildAdm3(string $name, string $code, Adm2 $adm2, ObjectManager $manager): Adm3
    {
        $adm3 = $manager->getRepository(Adm3::class)->findOneByCode($code);
        if (!$adm3) {
            $adm3 = (new Adm3())
                ->setAdm2($adm2)
                ->setName($name)
                ->setCode($code);

            $manager->persist($adm3);
        }

        return $adm3;
    }

    private function buildAdm4(string $name, string $code, Adm3 $adm3, ObjectManager $manager): Adm4
    {
        $adm4 = $manager->getRepository(Adm4::class)->findOneByCode($code);
        if (!$adm4) {
            $adm4 = (new Adm4())
                ->setAdm3($adm3)
                ->setName($name)
                ->setCode($code);

            $manager->persist($adm4);
        }

        return $adm4;
    }
}
