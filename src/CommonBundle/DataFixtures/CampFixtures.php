<?php

namespace CommonBundle\DataFixtures;

use BeneficiaryBundle\Entity\Camp;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\KernelInterface;

class CampFixtures extends Fixture implements DependentFixtureInterface
{
    /** @var KernelInterface */
    private $kernel;

    /**
     * CampFixtures constructor.
     *
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $location = $manager->getRepository(\CommonBundle\Entity\Location::class)->findBy([], null, 1)[0];

        $camp = (new Camp())
            ->setName('Camp David')
            ->setLocation($location);

        $manager->persist($camp);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            LocationTestFixtures::class,
        ];
    }
}
