<?php

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Institution;
use CommonBundle\Pagination\Paginator;
use NewApiBundle\Utils\CodeLists;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;

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
