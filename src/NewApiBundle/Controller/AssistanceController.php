<?php


namespace NewApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Mapper\AssistanceMapper;
use NewApiBundle\Repository\AssistanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AssistanceController extends Controller
{
    /**
     * @Rest\Get("/project/{projectId}/assistances", requirements={"projectId" = "\d+"})
     *
     * @param Request              $request
     * @param AssistanceRepository $assistanceRepository
     * @param AssistanceMapper     $mapper
     *
     * @return JsonResponse
     */
    public function getProjectAssistances(Request $request, AssistanceRepository $assistanceRepository, AssistanceMapper $mapper): JsonResponse
    {
        return $this->json($mapper->toFullArrays($assistanceRepository->getAllByProjectId($request->get('projectId'))));
    }
}