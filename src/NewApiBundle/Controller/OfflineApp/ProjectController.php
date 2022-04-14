<?php

declare(strict_types=1);

namespace NewApiBundle\Controller\OfflineApp;

use FOS\RestBundle\Controller\Annotations as Rest;
use ProjectBundle\Mapper\ProjectMapper;
use ProjectBundle\Repository\ProjectRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ProjectController extends AbstractOfflineAppController
{
    /**
     * @var ProjectRepository
     */
    private $projectRepository;

    /**
     * @var ProjectMapper
     */
    private $projectMapper;

    public function __construct(ProjectRepository $projectRepository, ProjectMapper $projectMapper)
    {
        $this->projectRepository = $projectRepository;
        $this->projectMapper = $projectMapper;
    }

    /**
     * @Rest\Get("/offline-app/v1/projects")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getProjects(Request $request): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $projects = $this->projectRepository->findByParams($this->getUser(), $countryIso3, null)
            ->getQuery()
            ->getResult();

        return $this->json($this->projectMapper->toFullArrays($projects));
    }

    /**
     * @deprecated This endpoint is not consumed by app because of different interface
     *
     * @Rest\Get("/offline-app/v2/projects")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $paginator = $this->projectRepository->findByParams($this->getUser(), $countryIso3, null);

        $response = $this->json($paginator->getQuery()->getResult());
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }
}
