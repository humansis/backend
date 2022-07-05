<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Entity\ScoringBlueprint;
use NewApiBundle\InputType\ScoringBlueprintFilterInputType;
use NewApiBundle\InputType\ScoringInputType;
use NewApiBundle\Repository\ScoringBlueprintRepository;
use NewApiBundle\Services\ScoringBlueprintService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Cache(expires="+1 hour", public=true)
 */
class ScoringBlueprintController extends AbstractController
{


    /** @var ScoringBlueprintService $scoringBlueprintService */
    private $scoringBlueprintService;

    /** @var ScoringBlueprintRepository $scoringBlueprintRepository */
    private $scoringBlueprintRepository;

    /**
     * @param ScoringBlueprintService    $scoringService
     * @param ScoringBlueprintRepository $scoringBlueprintRepository
     */
    public function __construct(
        ScoringBlueprintService $scoringBlueprintService,
        ScoringBlueprintRepository $scoringBlueprintRepository
    )
    {
        $this->scoringBlueprintService = $scoringBlueprintService;
        $this->scoringBlueprintRepository = $scoringBlueprintRepository;
    }

    /**
     * @Rest\Get("/web-app/v1/scorings")
     * @param Request                         $request
     * @param ScoringBlueprintFilterInputType $scoringFilterInputType
     *
     * @return JsonResponse
     */
    public function list(Request $request, ScoringBlueprintFilterInputType $scoringFilterInputType): JsonResponse
    {
        $scoringBlueprints = $this->scoringBlueprintRepository->findByParams($this->getCountryCode($request), null, $scoringFilterInputType);
        return $this->json($scoringBlueprints);
    }

    /**
     * @Rest\Post("/web-app/v1/scorings")
     * @param Request          $request
     * @param ScoringInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(Request $request, ScoringInputType $inputType): JsonResponse
    {
        $scoringBlueprint = $this->scoringBlueprintService->create($inputType, $this->getCountryCode($request));
        return $this->json($this->scoringBlueprintRepository->find($scoringBlueprint), 201);
    }

    /**
     * @Rest\Get("/web-app/v1/scorings/{id}")
     * @param ScoringBlueprint $scoringBlueprint
     *
     * @return JsonResponse
     */
    public function single(ScoringBlueprint $scoringBlueprint): JsonResponse
    {
        return $this->json($scoringBlueprint);
    }

    /**
     * @Rest\Delete("/web-app/v1/scorings/{id}")
     * @param ScoringBlueprint $scoringBlueprint
     *
     * @return JsonResponse
     */
    public function archive(ScoringBlueprint $scoringBlueprint): JsonResponse
    {

        $this->scoringBlueprintService->archive($scoringBlueprint);
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }




}
