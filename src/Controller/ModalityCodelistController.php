<?php

declare(strict_types=1);

namespace Controller;

use Enum\Modality;
use Enum\ModalityType;
use Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use Services\CodeListService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Cache(expires="+12 hours", public=true)
 */
class ModalityCodelistController extends AbstractController
{
    /** @var CodeListService */
    private $codeListService;

    public function __construct(CodeListService $codeListService)
    {
        $this->codeListService = $codeListService;
    }

    /**
     * @Rest\Get("/web-app/v1/modalities")
     *
     * @return JsonResponse
     */
    public function modalities(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(Modality::values());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/modalities/types")
     *
     * @return JsonResponse
     */
    public function allTypes(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(ModalityType::values());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/modalities/{code}/types")
     *
     * @param string $code
     *
     * @return JsonResponse
     */
    public function types(string $code): JsonResponse
    {
        $data = $this->codeListService->mapEnum(Modality::getModalityTypes($code));

        return $this->json(new Paginator($data));
    }
}
