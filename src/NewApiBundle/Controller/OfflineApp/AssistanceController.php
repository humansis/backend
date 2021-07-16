<?php

declare(strict_types=1);

namespace NewApiBundle\Controller\OfflineApp;

use DistributionBundle\Entity\Assistance;
use DistributionBundle\Repository\AssistanceRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Controller\AbstractController;
use NewApiBundle\InputType\AssistanceByProjectOfflineAppFilterInputType;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AssistanceController extends AbstractController
{
    /**
     * {@inheritdoc}
     */
    protected function json($data, $status = 200, $headers = [], $context = []): JsonResponse
    {
        if (!isset($context['offline-app'])) {
            $context['offline-app'] = true;
        }

        return parent::json($data, $status, $headers, $context);
    }

    /**
     * @Rest\Get("/offline-app/v2/projects/{id}/assistances")
     *
     * @param Project                                      $project
     * @param AssistanceByProjectOfflineAppFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function projectAssistances(Project $project, AssistanceByProjectOfflineAppFilterInputType $filter, Request $request): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        /** @var AssistanceRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Assistance::class);

        $assistances = $repository->findByProjectInOfflineApp($project, $countryIso3, $filter);

        return $this->json($assistances);
    }
}
