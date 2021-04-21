<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Entity\DistributedItem;
use NewApiBundle\InputType\DistributedItemFilterInputType;
use NewApiBundle\Request\Pagination;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DistributedItemController extends AbstractController
{
    /**
     * @Rest\Get("/beneficiaries/{id}/distributed-items")
     * @ParamConverter("beneficiary")
     *
     * @param Beneficiary $beneficiary
     *
     * @return JsonResponse
     */
    public function listByBeneficiary(Beneficiary $beneficiary): JsonResponse
    {
        $data = $this->getDoctrine()->getRepository(DistributedItem::class)
            ->findByBeneficiary($beneficiary);

        return $this->json($data);
    }

    /**
     * @Rest\Get("/households/{id}/distributed-items")
     * @ParamConverter("household")
     *
     * @param Household $household
     *
     * @return JsonResponse
     */
    public function listByHousehold(Household $household): JsonResponse
    {
        $data = $this->getDoctrine()->getRepository(DistributedItem::class)
            ->findByHousehold($household);

        return $this->json($data);
    }

    /**
     * @Rest\Get("/distributed-items")
     *
     * @param Request                        $request
     * @param DistributedItemFilterInputType $inputType
     * @param Pagination                     $pagination
     *
     * @return JsonResponse
     */
    public function distributedItems(Request $request, DistributedItemFilterInputType $inputType, Pagination $pagination): JsonResponse
    {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        $data = $this->getDoctrine()->getRepository(DistributedItem::class)
            ->findByParams($request->headers->get('country'), $inputType, $pagination);

        return $this->json($data);
    }
}
