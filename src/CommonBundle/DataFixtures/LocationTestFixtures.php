<?php


namespace CommonBundle\DataFixtures;

use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\Kernel;

class LocationTestFixtures extends Fixture implements FixtureGroupInterface
{
    private $countries = ["KHM", "SYR", "UKR", "ETH", "MNG", "ARM", "ZMB"];

    private $counter = 0;

    // code is suffixed by country code
    const ADM1_1 = 'ADM1Fst';
    const ADM1_2 = 'ADM1Snd';
    const ADM2_1 = 'ADM2Fst';
    const ADM2_2 = 'ADM2Snd';
    const ADM3_1 = 'ADM3Fst';
    const ADM3_2 = 'ADM3Snd';
    const ADM4_1 = 'ADM4Fst';
    const ADM4_2 = 'ADM4Snd';

    /** @var Kernel $kernel */
    private $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }


    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        if ($this->kernel->getEnvironment() === "prod") {
            echo "Can't run on production environment.";
            return;
        }
        return; //omit test until it will be differentiated between test and preview for testing envs
        foreach ($this->countries as $country) {
            $this->loadSimplyData($manager, $country);
        }
    }

    /**
     * This method must return an array of groups
     * on which the implementing class belongs to
     *
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['location', 'test'];
    }

    private function loadSimplyData(ObjectManager $manager, string $country)
    {
        $this->createAdm1($manager, $country, [self::ADM1_1.$country, self::ADM1_2.$country]);
        $manager->flush();
    }

    private function createAdm1(ObjectManager $manager, string $country, array $names = []) {
        foreach ($names as $name) {
            $adm1 = new Adm1();
            $adm1->setCountryISO3($country)
                ->setCode(sprintf('%.2s%03d', $country, ++$this->counter))
                ->setName($name);
            $manager->persist($adm1);

            $this->createAdm2($manager, $country, $adm1, [self::ADM2_1.$country, self::ADM2_2.$country]);
        }
    }

    private function createAdm2(ObjectManager $manager, string $country, Adm1 $adm1, array $names = []) {
        foreach ($names as $name) {
            $adm2 = new Adm2();
            $adm2->setAdm1($adm1)
                ->setCode(sprintf('%.2s%06d', $adm1->getCountryISO3(), ++$this->counter))
                ->setName($name);
            $manager->persist($adm2);

            $this->createAdm3($manager, $country, $adm2, [self::ADM3_1.$country, self::ADM3_2.$country]);
        }
    }

    private function createAdm3(ObjectManager $manager, string $country, Adm2 $adm2, array $names = []) {
        foreach ($names as $name) {
            $adm3 = new Adm3();
            $adm3->setAdm2($adm2)
                ->setCode(sprintf('%.2s%09d', $adm2->getAdm1()->getCountryISO3(), ++$this->counter))
                ->setName($name);
            $manager->persist($adm3);

            $this->createAdm4($manager, $adm3, [self::ADM4_1.$country, self::ADM4_2.$country]);
        }
    }

    private function createAdm4(ObjectManager $manager, Adm3 $adm3, array $names = []) {
        foreach ($names as $name) {
            $adm4 = new Adm4();
            $adm4->setAdm3($adm3)
                ->setCode(sprintf('%.2s%012d', $adm3->getAdm2()->getAdm1()->getCountryISO3(), ++$this->counter))
                ->setName($name);
            $manager->persist($adm4);
        }
    }
}
