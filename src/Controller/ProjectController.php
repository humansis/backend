<?php

declare(strict_types=1);

namespace Controller;

use Doctrine\Persistence\ManagerRegistry;
use Entity\Beneficiary;
use Entity\UserCountry;
use Entity\UserProject;
use Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\ProjectCreateInputType;
use InputType\ProjectFilterInputType;
use InputType\ProjectOrderInputType;
use InputType\ProjectUpdateInputType;
use Request\Pagination;
use Entity\Project;
use Repository\ProjectRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Entity\User;
use Utils\ExportTableServiceInterface;
use Utils\ProjectService;
use Utils\ProjectTransformData;

class ProjectController extends AbstractController
{
    public function __construct(private readonly ProjectRepository $projectRepository, private readonly ProjectService $projectService, private readonly ManagerRegistry $managerRegistry, private readonly ProjectTransformData $projectTransformData, private readonly ExportTableServiceInterface $exportTableService)
    {
    }

    /**
     * @Rest\Get("/web-app/v1/projects/{id}/summaries")
     *
     *
     */
    public function summaries(Request $request, Project $project): JsonResponse
    {
        if (true === $project->getArchived()) {
            throw $this->createNotFoundException();
        }

        $repository = $this->managerRegistry->getRepository(Beneficiary::class);

        $result = [];
        if ($request->query->has('code')) {
            foreach ($request->query->all('code') as $code) {
                $result[] = match ($code) {
                    'reached_beneficiaries' => ['code' => $code, 'value' => $repository->countAllInProject($project)],
                    default => throw new BadRequestHttpException('Invalid query parameter code.' . $code),
                };
            }
        }

        return $this->json(new Paginator($result));
    }

    /**
     * @Rest\Get("/web-app/v1/projects/exports")
     *
     *
     */
    public function exports(Request $request): Response
    {
        $countryIso3 = $request->headers->get('country');
        $type = $request->query->get('type');

        $projects = $this->projectRepository->getAllOfCountry($countryIso3);
        $exportableTable = $this->projectTransformData->transformData($projects);

        return $this->exportTableService->export($exportableTable, 'projects', $type);
    }

    /**
     * @Rest\Get("/web-app/v1/projects/{id}")
     * @Cache(lastModified="project.getLastModifiedAtIncludingBeneficiaries()", public=true)
     *
     *
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
     *
     */
    public function list(
        Request $request,
        ProjectFilterInputType $filter,
        ProjectOrderInputType $orderBy,
        Pagination $pagination
    ): JsonResponse {
        $countryIso3 = $request->headers->get('country');
        if (is_null($countryIso3)) {
            throw new BadRequestHttpException('Missing country header');
        }

        $projects = $this->projectRepository->findByParams(
            $this->getUser(),
            $countryIso3,
            $filter,
            $orderBy,
            $pagination
        );

        return $this->json($projects);
    }

    /**
     * @Rest\Post("/web-app/v1/projects")
     *
     *
     */
    public function create(ProjectCreateInputType $inputType): JsonResponse
    {
        $object = $this->projectService->create($inputType, $this->getUser());

        return $this->json($object);
    }

    /**
     * @Rest\Put("/web-app/v1/projects/{id}")
     *
     *
     */
    public function update(Project $project, ProjectUpdateInputType $inputType): JsonResponse
    {
        if ($project->getArchived()) {
            throw new BadRequestHttpException('Unable to update archived project.');
        }

        $object = $this->projectService->update($project, $inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Delete("/web-app/v1/projects/{id}")
     *
     *
     */
    public function delete(Project $project): JsonResponse
    {
        $this->projectService->delete($project);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Get("/web-app/v1/users/{id}/projects")
     *
     *
     */
    public function userProjects(User $user): JsonResponse
    {
        if ($user->getProjects()->count() > 0) {
            $projects = array_values(
                array_map(fn(UserProject $item) => $item->getProject(), $user->getProjects()->toArray())
            );

            return $this->json(new Paginator($projects));
        }

        if ($user->getCountries()->count() > 0) {
            $countries = array_values(
                array_map(fn(UserCountry $item) => $item->getId(), $user->getCountries()->toArray())
            );

            $data = $this->projectRepository->findByCountries($countries);

            return $this->json(new Paginator($data));
        }

        // user without related projects should have access to all projects
        $data = $this->projectRepository->findBy(['archived' => false]);

        return $this->json(new Paginator($data));
    }
}
