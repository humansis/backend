<?php

namespace ProjectBundle\Controller;

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
    * @Rest\Get("/projects")
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
    public function getProjectsAction()
    {
		// TODO check user rights

        $projects = $this->get('project.project_service')->findAll();
        $json = $this->get('serializer')->serialize($projects, 'json');

        return new Response($json, Response::HTTP_OK);
    }

	/**
    * Create a project
    * @Rest\Post("/projects")
    *
    * @SWG\Parameter(
    * 	  name="body",
    * 	  in="body",
    * 	  type="object",
    * 	  required=true,
    * 	  description="Body of the request",
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
    public function createProjectAction(Request $request)
    {
		// TODO check user rights

		try {
            $project = $this->get('project.project_service')->createProject($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage());
        }

		$json = $this->get('serializer')->serialize($project, 'json');

        return new Response($json, Response::HTTP_OK);
    }
}
