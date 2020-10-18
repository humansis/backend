<?php


namespace NewApiBundle\Controller;


use NewApiBundle\Mapper\AssistanceMapper;
use NewApiBundle\Model\HomeService;
use NewApiBundle\Repository\AssistanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;

class HomeController extends Controller
{
    /**
     * @Rest\Get("/home-summary", name="home-summary")
     * @param HomeService $homeService
     *
     * @return JsonResponse
     */
    public function getSummary(HomeService $homeService): JsonResponse
    {
        return $this->json($homeService->getSummary());
    }


    /**
     * @Rest\Get("/upcoming-assistances", name="home-summary")
     *
     * @param AssistanceRepository $assistanceRepository
     * @param AssistanceMapper     $mapper
     *
     * @return JsonResponse
     */
    public function getUpcomingAssists(AssistanceRepository $assistanceRepository, AssistanceMapper $mapper): JsonResponse
    {
        return $this->json($mapper->toFullArrays($assistanceRepository->getActiveByCountry('KHM')));
    }


}
