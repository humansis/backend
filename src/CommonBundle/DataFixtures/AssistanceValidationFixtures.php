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

        $assistances = $manager->getRepository(Assistance::class)->findBy([
        ]);

        foreach ($assistances as $assistance) {
            if ($assistance->getId() % 2 === 0) {
                $this->assistanceService->validateDistribution($assistance);
                echo 'v';
            } else {
                echo ".";
            }
            $manager->persist($assistance);
        }
        echo "\n";
        $manager->flush();
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
