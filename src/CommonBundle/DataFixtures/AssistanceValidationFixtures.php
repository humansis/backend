<?php

namespace CommonBundle\DataFixtures;

use DistributionBundle\Utils\AssistanceService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpKernel\Kernel;

class AssistanceValidationFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    private $assistanceService;

    private $kernel;

    private $countries = [];

    public function __construct(Kernel $kernel, array $countries, AssistanceService $assistanceService)
    {
        $this->assistanceService = $assistanceService;
        $this->kernel = $kernel;

        foreach ($countries as $country) {
            $this->countries[$country['iso3']] = $country;
        }
    }

    /**
     * Load data fixtures with the passed EntityManager.
     *
     * @param ObjectManager $manager
     *
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function load(ObjectManager $manager)
    {
        if ('prod' === $this->kernel->getEnvironment()) {
            return;
        }

        foreach ($this->countries as $iso3 => $details) {
            $project = $manager->getRepository(Project::class)->findOneBy([], ['id' => 'desc']);

            foreach ($project->getDistributions() as $assistance) {
                $this->assistanceService->validateDistribution($assistance);
                $manager->persist($assistance);
                echo ".";
            }
            echo "\n";
            $manager->flush();
        }


    }

    public function getDependencies(): array
    {
        return [
            AssistanceFixtures::class,
            BeneficiaryTestFixtures::class,
            BookletFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['preview'];
    }
}
