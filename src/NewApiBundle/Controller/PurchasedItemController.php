<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\PurchasedItemFilterInputType;
use NewApiBundle\Request\Pagination;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use NewApiBundle\Entity\PurchasedItem;
use NewApiBundle\Repository\PurchasedItemRepository;

class PurchasedItemController extends AbstractController
{
    /**
     * @Rest\Get("/beneficiaries/{id}/purchased-items")
     * @ParamConverter("beneficiary")
     *
     * @param Beneficiary $beneficiary
     *
     * @return JsonResponse
     */
    public function listByBeneficiary(Beneficiary $beneficiary): JsonResponse
    {
        /** @var PurchasedItemRepository $repository */
        $repository = $this->getDoctrine()->getRepository(PurchasedItem::class);

        $data = $repository->findByBeneficiary($beneficiary);

        return $this->json($data);
    }

    /**
     * @Rest\Get("/households/{id}/purchased-items")
     * @ParamConverter("household")
     *
     * @param Household $household
     *
     * @return JsonResponse
     */
    public function listByHousehold(Household $household): JsonResponse
    {
        /** @var PurchasedItemRepository $repository */
        $repository = $this->getDoctrine()->getRepository(PurchasedItem::class);

        $data = $repository->findByHousehold($household);

        return $this->json($data);
    }

    /**
     * @Rest\Get("/purchased-items")
     *
     * @return JsonResponse
     */
    public function list(Request $request, PurchasedItemFilterInputType $filterInputType, Pagination $pagination): JsonResponse
    {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        /** @var PurchasedItemRepository $repository */
        $repository = $this->getDoctrine()->getRepository(PurchasedItem::class);

        $data = $repository->findByParams($request->headers->get('country'), $filterInputType, $pagination);

        return $this->json($data);
    }
}
