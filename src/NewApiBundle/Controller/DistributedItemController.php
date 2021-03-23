<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use CommonBundle\Pagination\Paginator;
use DistributionBundle\Entity\DistributedItem;
use DistributionBundle\Repository\DistributedItemRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;

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
        /** @var DistributedItemRepository $repository */
        $repository = $this->getDoctrine()->getRepository(DistributedItem::class);

        $data = $repository->findDistributedToBeneficiary($beneficiary);

        return $this->json(new Paginator($data));
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
        /** @var DistributedItemRepository $repository */
        $repository = $this->getDoctrine()->getRepository(DistributedItem::class);

        $data = $repository->findDistributedToHousehold($household);

        return $this->json(new Paginator($data));
    }
}
