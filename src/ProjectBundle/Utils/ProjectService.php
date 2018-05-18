<?php

namespace ProjectBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ProjectBundle\Entity\Project;

class ProjectService
{
    protected $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

	/**
	 * Get all projects
	 *
     * @return array
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

	/**
	 * Create a project
	 *
	 * @param  Request $request
	 * @return Project
	 */
	public function createProject(Request $request)
	{
		$name = $request->request->get('name');
        $startDate =  new \DateTime($request->request->get('startDate'));
		$endDate =  new \DateTime($request->request->get('endDate'));
		$numberOfHouseholds = $request->request->get('numberOfHouseholds');
		$value = $request->request->get('value');
        $notes = $request->request->get('notes');

		if (empty($name) || empty($startDate) || empty($endDate) ||
			empty($numberOfHouseholds) || empty($value)) {
				throw new \Exception(
					"Supplied parameters do not match (name, startDate, endDate, numberOfHouseholds, value)",
					Response::HTTP_BAD_REQUEST
				);
		}

        // TODO check if project already exists
		// $project = $this->getRepository()->getUniqueProject();
        // if (!empty($project)) {
        //     throw new \Exception("This project already exists", Response::HTTP_BAD_REQUEST);
        // }

        $project = new Project();
        $project->setName($name);
        $project->setStartDate($startDate);
		$project->setEndDate($endDate);
		$project->setEndDate($endDate);
		$project->setNumberOfHouseholds($numberOfHouseholds);
        $project->setValue($value);

		$project->setNotes(!empty($notes) ? $notes : null);

        $this->em->persist($project);
        $this->em->flush();

        return $project;
	}

	/**
     * @return \Doctrine\ORM\EntityRepository|\UserBundle\Repository\ProjectRepository
     */
    private function getRepository()
    {
        return $this->em->getRepository('ProjectBundle:Project');
    }
}
