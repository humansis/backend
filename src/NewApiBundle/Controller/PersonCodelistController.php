<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\NationalId;
use CommonBundle\Pagination\Paginator;
use NewApiBundle\Enum\PhoneTypes;
use NewApiBundle\Utils\CodeLists;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * @Cache(expires="+5 days", public=true)
 */
class PersonCodelistController extends AbstractController
{
    /**
     * @Rest\Get("/persons/national-ids/types")
     *
     * @return JsonResponse
     */
    public function getNationalIdTypes(): JsonResponse
    {
        $data = CodeLists::mapEnum(NationalId::types());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/persons/phones/types")
     *
     * @return JsonResponse
     */
    public function getPhoneTypes(): JsonResponse
    {
        $data = CodeLists::mapEnum(PhoneTypes::values());

        return $this->json(new Paginator($data));
    }
}
