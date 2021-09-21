<?php


namespace CommonBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpKernel\Kernel;

class ProjectFixtures extends Fixture implements FixtureGroupInterface
{
    private $countries = ["KHM", "UKR", "SYR", "ETH", "MNG", "ARM", "ZMB"];

    private $explicitTestProjects = [
        ['Dev KHM Project', 1, 1, 'notes', 'KHM', 'KHM eng address', 'KHM local address'],
        ['Dev UKR Project', 1, 1, 'notes', 'UKR', 'UKR eng address', 'UKR local address'],
        ['Dev SYR Project', 1, 1, 'notes', 'SYR', 'SYR eng address', 'SYR local address'],
        ['Dev ETH Project', 1, 1, 'notes', 'ETH', 'ETH eng address', 'ETH local address'],
        ['Dev MNG Project', 1, 1, 'notes', 'MNG', 'MNG eng address', 'MNG local address'],
        ['Dev ARM Project', 1, 1, 'notes', 'ARM', 'ARM eng address', 'ARM local address'],
        ['Dev ZMB Project', 1, 1, 'notes', 'ZMB', 'ZMB eng address', 'ZMB local address'],
    ];

    private $countryProjectNameTemplate = "{adjective} test project";
    private $countryNameAdjectives = [
        'KHM' => 'Cambodian',
        'SYR' => 'Syrian',
        'UKR' => 'Ukrainian',
        'ETH' => 'Ethiopian',
        'MNG' => 'Mongolian',
        'ARM' => 'Armenian',
        'ZMB' => 'Zambian',
    ];

    private $kernel;


    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        if ($this->kernel->getEnvironment() === "prod") {
            echo __CLASS__ . " can't be running at production\n";
            return;
        }

        foreach ($this->explicitTestProjects as $datum) {
            $this->createProjectFromData($manager, $datum);
        }
        $manager->flush();

        foreach ($this->countries as $country) {
            $projectName = str_replace(
                '{adjective}',
                $this->countryNameAdjectives[$country],
                $this->countryProjectNameTemplate
            );
            $this->createProjectFromData($manager, [$projectName, 1, 0, 'notes', $country, "$country eng address", "$country local address"]);
        }
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param $country
     * @param array $data
     */
    private function createProjectFromData(ObjectManager $manager, array $data): void
    {
        $project = $manager->getRepository(Project::class)->findOneByName($data[0]);
        if ($project instanceof Project) {
            echo "User {$project->getName()} in {$project->getIso3()} already exists. Omit creation.\n";
        } else {
            $project = new Project();
            $project->setName($data[0])
                ->setStartDate(new \DateTime())
                ->setEndDate((new \DateTime())->add(new \DateInterval("P1M")))
                ->setNumberOfHouseholds($data[1])
                ->setTarget($data[2])
                ->setNotes($data[3])
                ->setIso3($data[4])
                ->setProjectInvoiceAddressEnglish($data[5])
                ->setProjectInvoiceAddressLocal($data[6]);
            $manager->persist($project);
            echo $project->getName()." created\n";
        }
    }

    public static function getGroups(): array
    {
        return ['test'];
    }
}
