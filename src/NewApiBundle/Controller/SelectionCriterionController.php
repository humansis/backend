<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;


use CommonBundle\Pagination\Paginator;
use DistributionBundle\Entity\Assistance;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Codelist\CodeLists;
use NewApiBundle\Component\SelectionCriteria\FieldDbTransformer;
use NewApiBundle\Component\SelectionCriteria\SelectionCriterionService;
use NewApiBundle\Enum\ConditionEnum;
use NewApiBundle\Enum\SelectionCriteriaTarget;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SelectionCriterionController extends AbstractController
{
    /** @var FieldDbTransformer */
    private $fieldDbTransformer;

    /** @var SelectionCriterionService */
    private $selectionCriterionService;

    public function __construct(FieldDbTransformer $fieldDbTransformer, SelectionCriterionService $selectionCriterionService)
    {
        $this->fieldDbTransformer = $fieldDbTransformer;
        $this->selectionCriterionService = $selectionCriterionService;
    }

    /**
     * @Rest\Get("/web-app/v1/selection-criteria/targets")
     * @Cache(expires="+5 days", public=true)
     *
     * @return JsonResponse
     */
    public function targets(): JsonResponse
    {
        $data = CodeLists::mapEnum(SelectionCriteriaTarget::values());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/selection-criteria/targets/{targetCode}/fields")
     *
     * @param Request $request
     * @param string  $targetCode
     *
     * @return JsonResponse
     */
    public function fields(Request $request, string $targetCode): JsonResponse
    {
        if (!in_array($targetCode, SelectionCriteriaTarget::values())) {
            throw $this->createNotFoundException();
        }

        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $data = $this->selectionCriterionService->findFieldsByTarget($targetCode, $countryIso3);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/selection-criteria/targets/{targetCode}/fields/{fieldCode}/conditions")
     * @param Request $request
     * @param string  $targetCode
     * @param string  $fieldCode
     * @return JsonResponse
     */
    public function conditions(Request $request, string $targetCode, string $fieldCode): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        try {
            $data = $this->selectionCriterionService->findFieldConditions($fieldCode, $targetCode, $countryIso3);
        } catch (\InvalidArgumentException|\BadMethodCallException $ex) {
            throw $this->createNotFoundException($ex->getMessage(), $ex);
        }

        $data = ConditionEnum::createCodeItems($data);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/selection-criteria")
     * @ParamConverter("assistance")
     * @Cache(expires="+5 days", public=true)
     *
     * @return JsonResponse
     */
    public function selectionCriteriaByAssistance(Assistance $assistance): JsonResponse
    {
        $data = [];
        foreach ($assistance->getSelectionCriteria() as $selectionCriterion) {
            $data[] = $this->fieldDbTransformer->toResponseArray($selectionCriterion);
        }

        return $this->json(new Paginator($data));
    }
}
