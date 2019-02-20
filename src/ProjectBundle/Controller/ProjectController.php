<?php

namespace ProjectBundle\Controller;

use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ProjectBundle\Entity\Project;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class ProjectController
 * @package ProjectBundle\Controller
 */
class ProjectController extends Controller
{
    /**
     * Get projects
     * @Rest\Get("/projects", name="get_all_projects")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Projects")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All Projects",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Project::class))
     *     )
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function getAllAction(Request $request)
    {
        $user = $this->getUser();
        $projects = $this->get('project.project_service')->findAll($request->request->get('__country'), $user);
        $json = $this->get('jms_serializer')
            ->serialize($projects, 'json', SerializationContext::create()->setGroups(['FullProject'])->setSerializeNull(true));

        return new Response($json, Response::HTTP_OK);
    }

    /**
     * Create a project
     * @Rest\Put("/projects", name="add_project")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Projects")
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
     *     description="Project created",
     *     @Model(type=Project::class)
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function addAction(Request $request)
    {
        $projectArray = $request->request->all();
        $country = $projectArray['__country'];
        unset($projectArray['__country']);
        $user = $this->getUser();

        try
        {
            $project = $this->get('project.project_service')->create($country, $projectArray, $user);
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
     * @Rest\Post("/projects/{id}", name="update_project")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE', project)")
     *
     * @SWG\Tag(name="Projects")
     *
     * @SWG\Parameter(
     *     name="Project",
     *     in="body",
     *     schema={},
     *     required=true,
     *     @Model(type=Project::class, groups={"FullProject"})
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Project updated",
     *     @Model(type=Project::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @param Project $project
     * @return Response
     */
    public function updateAction(Request $request, Project $project)
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
     * Delete a project
     * @Rest\Delete("/projects/{id}", name="delete_project")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE', project)")
     *
     * @SWG\Tag(name="Projects")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Project $project
     * @return Response
     */
    public function deleteAction(Project $project)
    {
        try
        {
            $this->get('project.project_service')->delete($project);
        }
        catch (\Exception $e)
        {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Add Beneficiaries to a project
     * @Rest\Post("/projects/{id}/beneficiaries/add", name="add_beneficiaries_project")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE', project)")
     *
     * @SWG\Tag(name="Projects")
     *
     * @SWG\Parameter(
     *     name="filter",
     *     in="body",
     *     required=true,
     *     type="array",
     *     schema={}
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Project updated"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @param Project $project
     * @return Response
     */
    public function addHouseholdsAction(Request $request, Project $project) {
        $beneficiaries = $request->request->get('beneficiaries');
        try
        {
            $result = $this->get('project.project_service')->addMultipleHouseholds($project, $beneficiaries);
        }
        catch(\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        if ($result)
            return new Response("" , Response::HTTP_OK);
        if (!$result)
            return new Response("", Response::HTTP_BAD_REQUEST);
    }
}
