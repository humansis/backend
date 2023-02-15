<?php

declare(strict_types=1);

namespace Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Entity\ScoringBlueprint;
use Exception\CsvParserException;
use InputType\ScoringBlueprintFilterInputType;
use InputType\ScoringInputType;
use InputType\ScoringPatchInputType;
use Repository\ScoringBlueprintRepository;
use Services\ScoringBlueprintService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[Rest\Route('/web-app/v1/scoring-blueprints')]
#[Cache(expires: '+1 hour', public: true)]
class ScoringBlueprintController extends AbstractController
{
    public function __construct(private readonly ScoringBlueprintService $scoringBlueprintService, private readonly ScoringBlueprintRepository $scoringBlueprintRepository)
    {
    }

    #[Rest\Get]
    public function list(Request $request, ScoringBlueprintFilterInputType $scoringFilterInputType): JsonResponse
    {
        $scoringBlueprints = $this->scoringBlueprintRepository->findByParams(
            $this->getCountryCode($request),
            null,
            $scoringFilterInputType
        );

        return $this->json($scoringBlueprints);
    }

    #[Rest\Post]
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

    #[Rest\Get('/{id}')]
    public function single(ScoringBlueprint $scoringBlueprint): JsonResponse
    {
        return $this->json($scoringBlueprint);
    }

    #[Rest\Patch('/{id}')]
    public function patch(ScoringBlueprint $scoringBlueprint, ScoringPatchInputType $inputType): JsonResponse
    {
        $this->scoringBlueprintService->patch($inputType, $scoringBlueprint);

        return $this->json($scoringBlueprint);
    }

    #[Rest\Delete('/{id}')]
    public function archive(ScoringBlueprint $scoringBlueprint): JsonResponse
    {
        $this->scoringBlueprintService->archive($scoringBlueprint);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Rest\Get('/{id}/content')]
    public function getContent(ScoringBlueprint $scoringBlueprint): StreamedResponse
    {
        $stream = $scoringBlueprint->getStream();
        $filename = "scoring-" . $scoringBlueprint->getName() . ".csv";

        return new StreamedResponse(function () use ($stream): never {
            fpassthru($stream);
            exit();
        }, \Symfony\Component\HttpFoundation\Response::HTTP_OK, [
            'Content-Transfer-Encoding',
            'binary',
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename='$filename'",
            'Content-Length' => fstat($stream)['size'],
        ]);
    }
}
