<?php

namespace ProjectBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Response;
use ProjectBundle\Entity\Project;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProjectService
{
    protected $em;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var ValidatorInterface $validator */
    private $validator;

    public function __construct(EntityManagerInterface $entityManager, Serializer $serializer, ValidatorInterface $validator)
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    /**
     * Get all projects
     *
     * @return array
     */
    public function findAll()
    {
        return $this->em->getRepository(Project::class)->findAll();
    }

    /**
     * Create a project
     *
     * @param array $projectArray
     * @return Project
     * @throws \Exception
     */
    public function createProject(array $projectArray)
    {
        $project = $this->serializer->deserialize(json_encode($projectArray), Project::class, 'json');

        $errors = $this->validator->validate($project);
        if (count($errors) > 0)
        {
            $errorsArray = [];
            foreach ($errors as $error)
            {
                $errorsArray[] = $error->getMessage();
            }
            throw new \Exception(json_encode($errorsArray), Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($project);
        $this->em->flush();

        return $project;
    }
}
