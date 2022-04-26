<?php

declare(strict_types=1);

namespace NewApiBundle\Controller\OfflineApp;

use FOS\RestBundle\Controller\Annotations as Rest;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ProjectController extends AbstractOfflineAppController
{
    /**
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

        $paginator = $this->getDoctrine()->getRepository(Project::class)->findByParams($this->getUser(), $countryIso3, null);

        $response = $this->json($paginator->getQuery()->getResult());
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }
}
