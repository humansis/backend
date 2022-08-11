<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use NewApiBundle\Entity\Beneficiary;
use NewApiBundle\Controller\ExportController;
use NewApiBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\ProjectCreateInputType;
use NewApiBundle\InputType\ProjectFilterInputType;
use NewApiBundle\InputType\ProjectOrderInputType;
use NewApiBundle\InputType\ProjectUpdateInputType;
use NewApiBundle\Request\Pagination;
use NewApiBundle\Entity\Project;
use NewApiBundle\Repository\ProjectRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use NewApiBundle\Entity\User;

class ProjectController extends AbstractController
{
    /**
     * @var ProjectRepository
     */
    private $projectRepository;

    public function __construct(ProjectRepository $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }

    /**
     * @Rest\Get("/web-app/v1/projects/{id}/summaries")
     *
     * @param Request $request
     * @param Project $project
     *
     * @return JsonResponse
     */
    public function summaries(Request $request, Project $project): JsonResponse
    {
        if (true === $project->getArchived()) {
            throw $this->createNotFoundException();
        }

        $repository = $this->getDoctrine()->getRepository(Beneficiary::class);

        $result = [];
        foreach ($request->query->get('code', []) as $code) {
            switch ($code) {
                case 'reached_beneficiaries':
                    $result[] = ['code' => $code, 'value' => $repository->countAllInProject($project)];
                    break;
                default:
                    throw new BadRequestHttpException('Invalid query parameter code.'.$code);
            }
        }

        return $this->json(new Paginator($result));
    }

    /**
     * @Rest\Get("/web-app/v1/projects/exports")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exports(Request $request): Response
    {
        $request->query->add(['projects' => $request->headers->get('country')]);

        return $this->forward(ExportController::class.'::exportAction', [], $request->query->all());
    }

    /**
     * @Rest\Get("/web-app/v1/projects/{id}")
     * @Cache(lastModified="project.getLastModifiedAtIncludingBeneficiaries()", public=true)
     *
     * @param Project $project
     *
     * @return JsonResponse
     */
    public function item(Project $project): JsonResponse
    {
        if (true === $project->getArchived()) {
            throw $this->createNotFoundException();
        }

        return $this->json($project);
    }

    /**
     * @Rest\Get("/web-app/v1/projects")
     *
     * @param Request                $request
     * @param ProjectFilterInputType $filter
     * @param ProjectOrderInputType  $orderBy
     * @param Pagination             $pagination
     *
     * @return JsonResponse
     */
    public function list(Request $request, ProjectFilterInputType $filter, ProjectOrderInputType $orderBy, Pagination $pagination): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $projects = $this->projectRepository->findByParams($this->getUser(), $countryIso3, $filter, $orderBy, $pagination);

        return $this->json($projects);
    }

    /**
     * @Rest\Post("/web-app/v1/projects")
     *
     * @param ProjectCreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(ProjectCreateInputType $inputType): JsonResponse
    {
        $object = $this->get('project.project_service')->create($inputType, $this->getUser());

        return $this->json($object);
    }

    /**
     * @Rest\Put("/web-app/v1/projects/{id}")
     *
     * @param Project                $project
     * @param ProjectUpdateInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(Project $project, ProjectUpdateInputType $inputType): JsonResponse
    {
        if ($project->getArchived()) {
            throw new BadRequestHttpException('Unable to update archived project.');
        }

        $object = $this->get('project.project_service')->update($project, $inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Delete("/web-app/v1/projects/{id}")
     *
     * @param Project $project
     *
     * @return JsonResponse
     */
    public function delete(Project $project): JsonResponse
    {
        $this->get('project.project_service')->delete($project);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Get("/web-app/v1/users/{id}/projects")
     *
     * @param User $user
     *
     * @return JsonResponse
     */
    public function userProjects(User $user): JsonResponse
    {
        if ($user->getProjects()->count() > 0) {
            $projects = array_values(array_map(function (\NewApiBundle\Entity\UserProject $item) {
                return $item->getProject();
            }, $user->getProjects()->toArray()));

            return $this->json(new Paginator($projects));
        }

        if ($user->getCountries()->count() > 0) {
            $countries = array_values(array_map(function (\NewApiBundle\Entity\UserCountry $item) {
                return $item->getId();
            }, $user->getCountries()->toArray()));

            $data = $this->projectRepository->findByCountries($countries);

            return $this->json(new Paginator($data));
        }

        // user without related projects should have access to all projects
        $data = $this->projectRepository->findBy(['archived' => false]);

        return $this->json(new Paginator($data));
    }
}
