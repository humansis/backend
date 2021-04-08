<?php

namespace CommonBundle\DataFixtures;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\Institution;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
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

<<<<<<< Updated upstream
        $assistances = $manager->getRepository(Assistance::class)->findBy([
        ]);
=======
        foreach ($this->countries as $iso3 => $details) {
            $project = $manager->getRepository(Project::class)->findOneBy([], ['id' => 'desc']);
>>>>>>> Stashed changes

        foreach ($assistances as $assistance) {
            if ($assistance->getId() % 2 === 0) {
                $this->assistanceService->validateDistribution($assistance);
                echo 'v';
            } else {
                echo ".";
            }
            $manager->persist($assistance);
        }
<<<<<<< Updated upstream
        echo "\n";
        $manager->flush();
=======

>>>>>>> Stashed changes
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
