<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Entity\ScoringBlueprint;
use NewApiBundle\Exception\CsvParserException;
use NewApiBundle\InputType\ScoringBlueprintFilterInputType;
use NewApiBundle\InputType\ScoringInputType;
use NewApiBundle\InputType\ScoringPatchInputType;
use NewApiBundle\Repository\ScoringBlueprintRepository;
use NewApiBundle\Services\ScoringBlueprintService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Rest\Route("/web-app/v1/scoring-blueprints")
 * @Cache(expires="+1 hour", public=true)
 */
class ScoringBlueprintController extends AbstractController
{


    /** @var ScoringBlueprintService $scoringBlueprintService */
    private $scoringBlueprintService;

    /** @var ScoringBlueprintRepository $scoringBlueprintRepository */
    private $scoringBlueprintRepository;

    /**
     * @param ScoringBlueprintService    $scoringBlueprintService
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
     * @Rest\Get()
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
     * @Rest\Post()
     * @param Request          $request
     * @param ScoringInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(Request $request, ScoringInputType $inputType): JsonResponse
    {
        try {
            $scoringBlueprint = $this->scoringBlueprintService->create($inputType, $this->getCountryCode($request));
            return $this->json($this->scoringBlueprintRepository->find($scoringBlueprint), 201);
        } catch (CsvParserException $ex) {
            // This is because CsvParserException is mapped to 500 and case where Base64 is not CSV
            throw new BadRequestHttpException($ex->getMessage());
        }

    }

    /**
     * @Rest\Get("/{id}")
     * @param ScoringBlueprint $scoringBlueprint
     *
     * @return JsonResponse
     */
    public function single(ScoringBlueprint $scoringBlueprint): JsonResponse
    {
        return $this->json($scoringBlueprint);
    }

    /**
     * @Rest\Patch("/{id}")
     * @param ScoringBlueprint      $scoringBlueprint
     * @param ScoringPatchInputType $inputType
     *
     * @return JsonResponse
     */
    public function patch(ScoringBlueprint $scoringBlueprint, ScoringPatchInputType $inputType): JsonResponse
    {
        $this->scoringBlueprintService->patch($inputType, $scoringBlueprint);
        return $this->json($scoringBlueprint);
    }

    /**
     * @Rest\Delete("/{id}")
     * @param ScoringBlueprint $scoringBlueprint
     *
     * @return JsonResponse
     */
    public function archive(ScoringBlueprint $scoringBlueprint): JsonResponse
    {
        $this->scoringBlueprintService->archive($scoringBlueprint);
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Get("/{id}/content")
     * @param ScoringBlueprint $scoringBlueprint
     *
     * @return StreamedResponse
     */
    public function getContent(ScoringBlueprint $scoringBlueprint): StreamedResponse
    {
        $stream = $scoringBlueprint->getStream();
        $filename = "scoring-".$scoringBlueprint->getName().".csv";
        return new StreamedResponse(function () use ($stream) {
            fpassthru($stream);
            exit();
        }, 200, [
            'Content-Transfer-Encoding', 'binary',
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename='$filename'",
            'Content-Length' => fstat($stream)['size'],
        ]);
    }



}
