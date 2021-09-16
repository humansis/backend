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
        ['Dev KHM Project', 1, 1, 'notes', 'KHM'],
        ['Dev UKR Project', 1, 1, 'notes', 'UKR'],
        ['Dev SYR Project', 1, 1, 'notes', 'SYR'],
        ['Dev ETH Project', 1, 1, 'notes', 'ETH'],
        ['Dev MNG Project', 1, 1, 'notes', 'MNG'],
        ['Dev ARM Project', 1, 1, 'notes', 'ARM'],
        ['Dev ZMB Project', 1, 1, 'notes', 'ZMB'],
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
            $this->createProjectFromData($manager, [$projectName, 1, 0, 'notes', $country]);
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
                ->setIso3($data[4]);
            $manager->persist($project);
            echo $project->getName()." created\n";
        }
    }

    public static function getGroups(): array
    {
        return ['test'];
    }
}
