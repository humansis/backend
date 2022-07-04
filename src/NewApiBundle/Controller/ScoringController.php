<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Codelist\CodeLists;
use NewApiBundle\Entity\Scoring;
use NewApiBundle\InputType\ScoringInputType;
use NewApiBundle\Services\ScoringService;
use ProjectBundle\Entity\Donor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Cache(expires="+1 hour", public=true)
 */
class ScoringController extends AbstractController
{
    /**
     * @var array
     */
    private $scoringConfigurations;

    /** @var ScoringService $scoringService */
    private $scoringService;
    /**
     * @param array $scoringConfigurations
     */
    public function __construct(
        array $scoringConfigurations,
        ScoringService $scoringService
    )
    {
        $this->scoringConfigurations = $scoringConfigurations;
        $this->scoringService = $scoringService;
    }

    /**
     * @Rest\Get("/web-app/v1/scorings")
     *
     * @return JsonResponse
     */
    public function createScoring(): JsonResponse
    {
        $scoringTypes = CodeLists::mapEnum(array_column($this->scoringConfigurations, 'name'));

        return $this->json(new Paginator($scoringTypes));
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
        $object = $this->scoringService->create($inputType, $this->getCountryCode($request));
        return $this->json($object);
    }

    /**
     * @Rest\Get("/web-app/v1/scorings")
     *
     * @return JsonResponse
     */
    public function getScorings(): JsonResponse
    {
        $scoringTypes = CodeLists::mapEnum(array_column($this->scoringConfigurations, 'name'));

        return $this->json(new Paginator($scoringTypes));
    }

    /**
     * @Rest\Delete("/web-app/v1/scorings/{id}")
     * @param Scoring $scoring
     *
     * @return JsonResponse
     */
    public function archive(Scoring $scoring): JsonResponse
    {

        $this->scoringService->archive($scoring);
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }




}
