<?php

namespace DataFixtures\Beneficiaries;

use Entity\Beneficiary;
use Entity\Community;
use Entity\Household;
use Entity\Institution;
use DataFixtures\AssistanceFixtures;
use DataFixtures\BeneficiaryTestFixtures;
use DataFixtures\ProjectFixtures;
use Entity\Assistance;
use Entity\AssistanceBeneficiary;
use Enum\AssistanceTargetType;
use Utils\AssistanceService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Entity\Project;
use Symfony\Component\HttpKernel\Kernel;

class AssistanceBeneficiaryFixtures extends Fixture implements DependentFixtureInterface//, FixtureGroupInterface
{
    private $distributionService;

    private $kernel;

    public function __construct(Kernel $kernel, AssistanceService $distributionService)
    {
        $this->distributionService = $distributionService;
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

        $projects = $manager->getRepository(Project::class)->findAll();
        foreach ($projects as $project) {
            echo $project->getName()."#{$project->getId()}: \n";
            $assistances = $manager->getRepository(Assistance::class)->findBy([
                'project' => $project,
            ], ['id' => 'asc']);

            foreach ($assistances as $assistance) {
                echo "P#{$project->getId()} - ".$assistance->getName().": ";
                if ($assistance->getCommodities()[0]->getModalityType()->getName() === 'Smartcard') continue;
                switch ($assistance->getTargetType()) {
                    case AssistanceTargetType::INDIVIDUAL:
                        $this->addBNFsToAssistance($manager, $assistance, $project);
                        break;
                    case AssistanceTargetType::HOUSEHOLD:
                        $this->addHHsToAssistance($manager, $assistance, $project);
                        break;
                    case AssistanceTargetType::INSTITUTION:
                        $this->addInstsToAssistance($manager, $assistance, $project);
                        break;
                    case AssistanceTargetType::COMMUNITY:
                        $this->addCommsToAssistance($manager, $assistance, $project);
                        break;
                }
                $manager->persist($assistance);
                echo "\n";
            }
            $manager->persist($project);
            $manager->flush();
        }
    }

    public function getDependencies(): array
    {
        return [
            ProjectFixtures::class,
            AssistanceFixtures::class,
            BeneficiaryTestFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['test'];
    }

    private function addBNFsToAssistance(ObjectManager $manager, Assistance $assistance, Project $project): void
    {
        $BNFs = $manager->getRepository(Beneficiary::class)->getUnarchivedByProject($project);
        echo "(".count($BNFs).") ";
        $count = 0;
        foreach ($BNFs as $beneficiary) {
            $bnf = (new AssistanceBeneficiary())
                ->setBeneficiary($beneficiary)
                ->setAssistance($assistance)
                ->setJustification('added randomly in fixtures')
            ;
            $assistance->addAssistanceBeneficiary($bnf);
            $manager->persist($bnf);
            echo "B";
            if (++$count == 3) return;
        }
    }

    private function addHHsToAssistance(ObjectManager $manager, Assistance $assistance, Project $project): void
    {
        $HHs = $manager->getRepository(Household::class)->getUnarchivedByProject($project)->getQuery()->getResult();
        echo "(".count($HHs).") ";
        $count = 0;
        /** @var Household $household */
        foreach ($HHs as $household) {
            if (!$household->getHouseholdHead()) {
                echo 'h';
                continue;
            }
            $bnf = (new AssistanceBeneficiary())
                ->setBeneficiary($household->getHouseholdHead())
                ->setAssistance($assistance)
                ->setJustification('added randomly in fixtures')
            ;
            $assistance->addAssistanceBeneficiary($bnf);
            $manager->persist($bnf);
            echo "H";
            if (++$count == 3) return;
        }
    }

    private function addInstsToAssistance(ObjectManager $manager, Assistance $assistance, Project $project): void
    {
        $institutions = $manager->getRepository(Institution::class)->getUnarchivedByProject($project);
        echo "(".count($institutions).") ";
        $count = 0;
        foreach ($institutions as $institution) {
            $bnf = (new AssistanceBeneficiary())
                ->setBeneficiary($institution)
                ->setAssistance($assistance)
                ->setJustification('added randomly in fixtures')
            ;
            $assistance->addAssistanceBeneficiary($bnf);
            $manager->persist($bnf);
            echo "I";
            if (++$count == 3) return;
        }
    }

    private function addCommsToAssistance(ObjectManager $manager, Assistance $assistance, Project $project): void
    {
        $communities = $manager->getRepository(Community::class)->getUnarchivedByProject($project);
        echo "(".count($communities).") ";
        $count = 0;
        foreach ($communities as $community) {
            $bnf = (new AssistanceBeneficiary())
                ->setBeneficiary($community)
                ->setAssistance($assistance)
                ->setRemoved(false)
                ->setJustification('added randomly in fixtures')
            ;
            $assistance->addAssistanceBeneficiary($bnf);
            $manager->persist($bnf);
            echo "C";
            if (++$count == 3) return;
        }
    }
}
