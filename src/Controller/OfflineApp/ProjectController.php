<?php

declare(strict_types=1);

namespace Controller\OfflineApp;

use FOS\RestBundle\Controller\Annotations as Rest;
use MapperDeprecated\ProjectMapper;
use Repository\ProjectRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ProjectController extends AbstractOfflineAppController
{
    public function __construct(private readonly ProjectRepository $projectRepository, private readonly ProjectMapper $projectMapper)
    {
    }

    /**
     * @Rest\Get("/offline-app/v1/projects")
     *
     *
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
     *
     * @deprecated This endpoint is not consumed by app because of different interface
     * @Rest\Get("/offline-app/v2/projects")
     *
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
