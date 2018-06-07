<?php

namespace ProjectBundle\Controller;

use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use ProjectBundle\Entity\Project;

class ProjectController extends Controller
{
    /**
     * Get projects
     * @Rest\Get("/projects", name="get_all_projects")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK",
     * )
     *
     * @SWG\Tag(name="Projects")
     *
     * @return Response
     */
    public function getAllAction()
    {
        // TODO check user rights

        $projects = $this->get('project.project_service')->findAll();
        $json = $this->get('jms_serializer')
            ->serialize($projects, 'json', SerializationContext::create()->setGroups(['FullProject'])->setSerializeNull(true));

        return new Response($json, Response::HTTP_OK);
    }

    /**
     * Get a project
     * @Rest\Get("/projects/{id}", name="get_project")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK",
     * )
     *
     * @SWG\Tag(name="Project")
     *
     * @return Response
     */
    public function getAction(Project $project)
    {
        $json = $this->get('jms_serializer')
            ->serialize($project, 'json', SerializationContext::create()->setGroups(['FullProject'])->setSerializeNull(true));

        return new Response($json, Response::HTTP_OK);
    }

    /**
     * Create a project
     * @Rest\Put("/projects", name="create_project")
     *
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="object",
     *      required=true,
     *      description="Body of the request",
     * 	  @SWG\Schema(ref=@Model(type=Project::class))
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK",
     * )
     *
     * @SWG\Tag(name="Projects")
     *
     * @param Request $request
     * @return Response
     */
    public function createAction(Request $request)
    {
        $projectArray = $request->request->all();
        $user = $this->getUser();

        try
        {
            $project = $this->get('project.project_service')->create($projectArray, $user);
        }
        catch (\Exception $e)
        {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        $json = $this->get('jms_serializer')
            ->serialize($project, 'json', SerializationContext::create()->setGroups(['FullProject'])->setSerializeNull(true));
        return new Response($json, Response::HTTP_OK);
    }

    /**
     * TODO VOTER POUR CHECKER QUE PROJECT EST PAS ARCHIVED
     * Edit a project
     * @Rest\Post("/projects/{id}", name="edit_project")
     *
     * @param Request $request
     * @param Project $project
     * @return Response
     */
    public function editAction(Request $request, Project $project)
    {
        $projectArray = $request->request->all();
        try
        {
            $project = $this->get('project.project_service')->edit($project, $projectArray);
        }
        catch (\Exception $e)
        {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        $json = $this->get('jms_serializer')
            ->serialize($project, 'json', SerializationContext::create()->setGroups(['FullProject'])->setSerializeNull(true));
        return new Response($json, Response::HTTP_OK);
    }

    /**
     * Edit a project
     * @Rest\Delete("/projects/{id}", name="delete_project")
     *
     * @param Project $project
     * @return Response
     */
    public function deleteAction(Project $project)
    {
        try
        {
            $valid = $this->get('project.project_service')->delete($project);
        }
        catch (\Exception $e)
        {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        if ($valid)
            return new Response("", Response::HTTP_OK);
        if (!$valid)
            return new Response("", Response::HTTP_BAD_REQUEST);
    }
}
