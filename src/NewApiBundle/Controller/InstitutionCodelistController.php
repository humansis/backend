<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use NewApiBundle\Entity\Institution;
use CommonBundle\Pagination\Paginator;
use NewApiBundle\Services\CodeListService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * @Cache(expires="+5 days", public=true)
 */
class InstitutionCodelistController extends AbstractController
{
    /** @var CodeListService */
    private $codeListService;

    public function __construct(CodeListService $codeListService)
    {
        $this->codeListService = $codeListService;
    }
    
    /**
     * @Rest\Get("/web-app/v1/institutions/types")
     *
     * @return JsonResponse
     */
    public function getInstitutionTypes(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(Institution::TYPE_ALL);

        return $this->json(new Paginator($data));
    }
}
