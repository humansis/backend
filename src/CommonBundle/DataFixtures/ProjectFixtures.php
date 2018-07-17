<?php


namespace CommonBundle\DataFixtures;


use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use ProjectBundle\Entity\Project;

class ProjectFixtures extends Fixture
{

    private $data = [
        ['Dev Project', 1, 1, 'notes', 'KHM']
    ];

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $datum)
        {
            $project = $manager->getRepository(Project::class)->findByName($datum[0]);
            if (!$project instanceof Project)
            {
                $project = new Project();
                $project->setName($datum[0])
                    ->setStartDate(new \DateTime())
                    ->setEndDate((new \DateTime())->add(new \DateInterval("P1M")))
                    ->setNumberOfHouseholds($datum[1])
                    ->setValue($datum[2])
                    ->setNotes($datum[3])
                    ->setIso3($datum[4]);
                $manager->persist($project);
                $manager->flush();
            }
        }
    }
}