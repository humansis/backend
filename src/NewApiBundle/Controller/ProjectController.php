<?php

namespace NewApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\ProjectOrderInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProjectController extends AbstractController
{
    /**
     * @Rest\Get("/projects")
     *
     * @param Pagination            $pagination
     * @param ProjectOrderInputType $orderBy
     *
     * @return JsonResponse
     */
    public function getProjects(Pagination $pagination, ProjectOrderInputType $orderBy): JsonResponse
    {
        $repository = $this->getDoctrine()->getRepository(Project::class);

        $projects = $repository->findAll($orderBy, $pagination);

        return $this->json($projects);
    }

    /**
     * @Rest\Get("/projects/{id}")
     *
     * @param Project $project
     * @return JsonResponse
     */
    public function getProject(Project $project)
    {
        return $this->json($project);
    }
}
