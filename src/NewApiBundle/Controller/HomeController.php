<?php


namespace NewApiBundle\Controller;


use NewApiBundle\Model\HomeService;
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
    public function getSummary(HomeService $homeService)
    {
        return $this->json($homeService->getSummary());
    }
}