<?php

declare(strict_types=1);

namespace Controller\WebApp\Assistance;

use BadMethodCallException;
use InvalidArgumentException;
use Pagination\Paginator;
use Entity\Assistance;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\SelectionCriteria\SelectionCriterionService;
use Controller\AbstractController;
use Enum\SelectionCriteriaTarget;
use Services\CodeListService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SelectionCriterionController extends AbstractController
{
    public function __construct(private readonly CodeListService $codeListService)
    {
    }

    #[Rest\Get('/web-app/v1/selection-criteria/targets')]
    #[Cache(expires: '+12 hours', public: true)]
    public function targets(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(SelectionCriteriaTarget::values());

        return $this->json(new Paginator($data));
    }

    #[Rest\Get('/web-app/v1/selection-criteria/targets/{targetCode}/fields')]
    public function fields(
        Request $request,
        string $targetCode,
        SelectionCriterionService $selectionCriterionService
    ): JsonResponse {
        if (!in_array($targetCode, SelectionCriteriaTarget::values())) {
            throw $this->createNotFoundException();
        }

        $countryIso3 = $request->headers->get('country');
        if (is_null($countryIso3)) {
            throw new BadRequestHttpException('Missing country header');
        }

        $data = $selectionCriterionService->findFieldsByTarget($targetCode, $countryIso3);

        return $this->json(new Paginator($data));
    }

    #[Rest\Get('/web-app/v1/selection-criteria/targets/{targetCode}/fields/{fieldCode}/conditions')]
    public function conditions(
        Request $request,
        string $targetCode,
        string $fieldCode,
        SelectionCriterionService $selectionCriterionService
    ): JsonResponse {
        $countryIso3 = $request->headers->get('country');
        if (is_null($countryIso3)) {
            throw new BadRequestHttpException('Missing country header');
        }

        try {
            $data = $selectionCriterionService->findFieldConditions($fieldCode, $targetCode, $countryIso3);
        } catch (InvalidArgumentException | BadMethodCallException $ex) {
            throw $this->createNotFoundException($ex->getMessage(), $ex);
        }

        $data = $this->codeListService->mapEnum($data);

        return $this->json(new Paginator($data));
    }

    #[Rest\Get('/web-app/v1/assistances/{id}/selection-criteria')]
    #[ParamConverter('assistance')]
    public function selectionCriteriaByAssistance(Assistance $assistance, Request $request): JsonResponse
    {
        $response = $this->json(new Paginator($assistance->getSelectionCriteria()));
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }
}
