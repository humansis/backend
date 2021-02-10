<?php

namespace CommonBundle\DataFixtures;

use CommonBundle\Controller\CountryController;
use DistributionBundle\Utils\AssistanceService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpKernel\KernelInterface;

class AssistanceValidationFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    /** @var AssistanceService */
    private $assistanceService;
    /** @var KernelInterface */
    private $kernel;

    public function __construct(KernelInterface $kernel, AssistanceService $assistanceService)
    {
        $this->assistanceService = $assistanceService;
        $this->kernel = $kernel;
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

        foreach (CountryController::COUNTRIES as $iso3 => $details) {
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
