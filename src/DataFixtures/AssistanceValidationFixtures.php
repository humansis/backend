<?php

namespace DataFixtures;

use Entity\User;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Utils\AssistanceService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Component\Country\Countries;
use Entity\Project;
use Symfony\Component\HttpKernel\Kernel;

class AssistanceValidationFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function __construct(private readonly Kernel $kernel, private readonly Countries $countries, private readonly AssistanceService $assistanceService)
    {
    }

    /**
     * Load data fixtures with the passed EntityManager.
     *
     *
     * @throws ValidationException
     */
    public function load(ObjectManager $manager)
    {
        if ('prod' === $this->kernel->getEnvironment()) {
            return;
        }

        /** @var User $user */
        $user = $this->getReference('user_admin');

        foreach ($this->countries->getAll() as $country) {
            $project = $manager->getRepository(Project::class)->findOneBy([], ['id' => 'desc']);

            foreach ($project->getDistributions() as $assistance) {
                $this->assistanceService->validateDistribution($assistance, $user);
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
