<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Enum\Domain;
use NewApiBundle\Enum\Modality;
use NewApiBundle\Enum\ModalityType;
use NewApiBundle\Services\CodeListService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Cache(expires="+5 days", public=true)
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
        $data = $this->codeListService->mapEnum(Modality::values(), Domain::ENUMS);
        
        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/modalities/types")
     *
     * @return JsonResponse
     */
    public function allTypes(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(ModalityType::getPublicValues(), Domain::ENUMS);

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
        $data = $this->codeListService->mapEnum(Modality::getModalityTypes($code), Domain::ENUMS);

        return $this->json(new Paginator($data));
    }
}
