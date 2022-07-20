<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use NewApiBundle\Entity\Modality;
use NewApiBundle\Entity\ModalityType;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Enum\Domain;
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
        $modalities = $this->getDoctrine()
            ->getRepository(Modality::class)
            ->getNames();

        $data = $this->codeListService->mapEnum($modalities, Domain::ENUMS);
        
        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/modalities/types")
     *
     * @return JsonResponse
     */
    public function allTypes(): JsonResponse
    {
        $types = $this->getDoctrine()
            ->getRepository(ModalityType::class)
            ->getPublicNames();

        $data = $this->codeListService->mapEnum($types, Domain::ENUMS);

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
        $types = $this->getDoctrine()
            ->getRepository(ModalityType::class)
            ->getPublicNames($code);

        $data = $this->codeListService->mapEnum($types, Domain::ENUMS);

        return $this->json(new Paginator($data));
    }
}
