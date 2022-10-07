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
    /** @var CodeListService */
    private $codeListService;

    public function __construct(CodeListService $codeListService)
    {
        $this->codeListService = $codeListService;
    }

    /**
     * @Rest\Get("/web-app/v1/selection-criteria/targets")
     * @Cache(expires="+5 days", public=true)
     *
     * @return JsonResponse
     */
    public function targets(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(SelectionCriteriaTarget::values());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/selection-criteria/targets/{targetCode}/fields")
     *
     * @param Request $request
     * @param string $targetCode
     * @param SelectionCriterionService $selectionCriterionService
     *
     * @return JsonResponse
     */
    public function fields(
        Request $request,
        string $targetCode,
        SelectionCriterionService $selectionCriterionService
    ): JsonResponse {
        if (!in_array($targetCode, SelectionCriteriaTarget::values())) {
            throw $this->createNotFoundException();
        }

        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $data = $selectionCriterionService->findFieldsByTarget($targetCode, $countryIso3);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/selection-criteria/targets/{targetCode}/fields/{fieldCode}/conditions")
     * @param Request $request
     * @param string $targetCode
     * @param string $fieldCode
     * @param SelectionCriterionService $selectionCriterionService
     *
     * @return JsonResponse
     */
    public function conditions(
        Request $request,
        string $targetCode,
        string $fieldCode,
        SelectionCriterionService $selectionCriterionService
    ): JsonResponse {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
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

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/selection-criteria")
     * @ParamConverter("assistance")
     * @Cache(expires="+5 days", public=true)
     *
     * @param Assistance $assistance
     *
     * @return JsonResponse
     */
    public function selectionCriteriaByAssistance(Assistance $assistance): JsonResponse
    {
        return $this->json(new Paginator($assistance->getSelectionCriteria()));
    }
}
