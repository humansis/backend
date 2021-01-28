<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Institution;
use CommonBundle\Pagination\Paginator;
use NewApiBundle\Utils\CodeLists;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * @Cache(expires="+5 days", public=true)
 */
class InstitutionCodelistController extends Controller
{
    /**
     * @Rest\Get("/institutions/types")
     *
     * @return JsonResponse
     */
    public function getInstitutionTypes(): JsonResponse
    {
        $data = CodeLists::mapEnum(Institution::TYPE_ALL);

        return $this->json(new Paginator($data));
    }
}
