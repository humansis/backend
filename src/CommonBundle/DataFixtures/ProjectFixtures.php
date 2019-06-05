<?php


namespace CommonBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpKernel\Kernel;

class ProjectFixtures extends Fixture
{
    private $data = [
        ['Dev Project', 1, 1, 'notes', 'KHM']
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
        if ($this->kernel->getEnvironment() !== "prod") {
            foreach ($this->data as $datum) {
                $project = $manager->getRepository(Project::class)->findOneByName($datum[0]);
                if (!$project instanceof Project) {
                    $project = new Project();
                    $project->setName($datum[0])
                    ->setStartDate(new \DateTime())
                    ->setEndDate((new \DateTime())->add(new \DateInterval("P1M")))
                    ->setNumberOfHouseholds($datum[1])
                    ->setTarget($datum[2])
                    ->setNotes($datum[3])
                    ->setIso3($datum[4]);
                    $manager->persist($project);
                    $manager->flush();
                }
            }
        }
    }
}
