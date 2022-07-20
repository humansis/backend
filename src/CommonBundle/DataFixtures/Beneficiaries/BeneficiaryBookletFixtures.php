<?php

declare(strict_types=1);

namespace CommonBundle\DataFixtures\Beneficiaries;

use NewApiBundle\Entity\Beneficiary;
use NewApiBundle\Entity\Household;
use CommonBundle\Controller\CountryController;
use CommonBundle\DataFixtures\BeneficiaryTestFixtures;
use CommonBundle\DataFixtures\BookletFixtures;
use CommonBundle\DataFixtures\ProjectFixtures;
use NewApiBundle\Entity\Assistance;
use NewApiBundle\Enum\AssistanceTargetType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use NewApiBundle\Entity\Project;
use Symfony\Component\HttpKernel\Kernel;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Utils\BookletService;

class BeneficiaryBookletFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    private $kernel;

    /** @var BookletService */
    private $bookletService;


    public function __construct(Kernel $kernel, BookletService $bookletService)
    {
        $this->kernel = $kernel;
        $this->bookletService = $bookletService;
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     * @throws Exception
     */
    public function load(ObjectManager $manager)
    {
        if ($this->kernel->getEnvironment() === "prod") {
            echo __CLASS__ . " can't be running at production\n";
            return;
        }

        $projects = $manager->getRepository(Project::class)->findBy(['archived' => false], ['id' => 'asc']);

        foreach ($projects as $project) {
            $voucherAssistances = $manager->getRepository(Assistance::class)->findBy([
                'project' => $project,
                'targetType' => AssistanceTargetType::INDIVIDUAL,
            ], ['id' => 'asc'], 2);
            $bookletGenerator = $this->bookletGenerator($manager, $project->getIso3());

            foreach ($voucherAssistances as $assistance) {
                echo $project->getName()." - {$assistance->getId()}# {$assistance->getName()}: ({$assistance->getDistributionBeneficiaries()->count()} {$assistance->getTargetType()})";
                foreach ($assistance->getDistributionBeneficiaries() as $distributionBeneficiary) {
                    $booklet = $bookletGenerator->current();
                    if (null === $booklet) {
                        echo '_';
                        $bookletGenerator->next();
                        continue;
                    }
                    if (
                        $distributionBeneficiary->getBeneficiary() instanceof Household
                        && null !== $distributionBeneficiary->getBeneficiary()->getHouseholdHead())
                    {
                        $this->bookletService->assign($booklet, $distributionBeneficiary->getAssistance(), $distributionBeneficiary->getBeneficiary()->getHouseholdHead());
                    }
                    if ($distributionBeneficiary->getBeneficiary() instanceof Beneficiary) {
                        $this->bookletService->assign($booklet, $distributionBeneficiary->getAssistance(), $distributionBeneficiary->getBeneficiary());
                    }

                    $bookletGenerator->next();
                    echo '.';
                }
                echo "\n";
            }
        }
    }

    private function bookletGenerator(ObjectManager $manager, string $country): iterable
    {
        $booklets = $manager->getRepository(Booklet::class)->getAllBy($country, 0, 1000, [], [[
            'category' =>'status',
            'filter' => [Booklet::UNASSIGNED],
        ]])[1];
        foreach ($booklets as $booklet) {
            if ($booklet->getStatus() !== Booklet::UNASSIGNED) continue;
            yield $booklet;
        }
    }

    public static function getGroups(): array
    {
        return ['preview'];
    }

    public function getDependencies()
    {
        return [
            BookletFixtures::class,
            BeneficiaryTestFixtures::class,
            ProjectFixtures::class,
            InstitutionFixture::class,
            CommunityFixture::class,
        ];
    }
}
