<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\SmartcardRedemptionBatchCreateInputType;
use NewApiBundle\Request\Pagination;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use VoucherBundle\Controller\SmartcardController;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\SmartcardRedemptionBatch;
use VoucherBundle\Entity\Vendor;

class SmartcardRedemptionBatchController extends AbstractController
{
    /**
     * @Rest\Get("/web-app/v1/smartcard-redemption-batches/{id}/exports")
     * @ParamConverter("redemptionBatch", class="VoucherBundle\Entity\SmartcardRedemptionBatch")
     *
     * @param SmartcardRedemptionBatch $redemptionBatch
     *
     * @return JsonResponse
     */
    public function export(SmartcardRedemptionBatch $redemptionBatch): Response
    {
        return $this->forward(SmartcardController::class.'::export', ['batch' => $redemptionBatch]);
    }

    /**
     * @Rest\Get("/web-app/v1/vendors/{id}/smartcard-redemption-batches")
     *
     * @param Vendor $vendor
     * @param Pagination $pagination
     *
     * @return JsonResponse
     */
    public function batches(Vendor $vendor, Pagination $pagination): JsonResponse
    {
        $batches = $this->getDoctrine()->getRepository(SmartcardRedemptionBatch::class)
            ->findByVendor($vendor, $pagination);

        return $this->json($batches);
    }

    /**
     * @Rest\Get("/vendor-app/v2/vendors/{id}/smartcard-redemption-batches")
     *
     * @param Vendor $vendor
     * @param Pagination $pagination
     *
     * @return JsonResponse
     */
    public function batchesForVendorApp(Vendor $vendor, Pagination $pagination): JsonResponse
    {
        return $this->forward(self::class.'::batches', ['vendor' => $vendor, 'pagination' => $pagination]);
    }

    /**
     * @Rest\Post("/web-app/v1/vendors/{id}/smartcard-redemption-batches")
     *
     * @param Vendor $vendor
     *
     * @return JsonResponse
     */
    public function create(Vendor $vendor, SmartcardRedemptionBatchCreateInputType $inputType): JsonResponse
    {
        //backward compatibility
        $newBatch = new \VoucherBundle\InputType\SmartcardRedemtionBatch();
        $newBatch->setPurchases($inputType->getPurchaseIds());

        $redemptionBath = $this->get('smartcard_service')->redeem($vendor, $newBatch, $this->getUser());

        return $this->json($redemptionBath);
    }

    /**
     * @Rest\Get("/web-app/v1/vendors/{id}/smartcard-redemption-candidates")
     *
     * @param Vendor $vendor
     *
     * @return JsonResponse
     */
    public function candidates(Vendor $vendor): JsonResponse
    {
        $candidates = $this->getDoctrine()->getRepository(SmartcardPurchase::class)
            ->countPurchasesToRedeem($vendor);

        return $this->json(new Paginator($candidates));
    }

    /**
     * @Rest\Get("/vendor-app/v2/vendors/{id}/smartcard-redemption-candidates")
     * @deprecated use $this->candidatesForVendorApp()
     *
     * @param Vendor $vendor
     *
     * @return JsonResponse
     */
    public function candidatesForVendorAppDeprecated(Vendor $vendor): Response
    {
        return $this->forward(self::class.'::candidates', ['vendor' => $vendor]);
    }

    /**
     * @Rest\Get("/vendor-app/v3/vendors/{id}/smartcard-redemption-candidates")
     *
     * @param Vendor $vendor
     *
     * @return JsonResponse
     */
    public function candidatesForVendorApp(Vendor $vendor): Response
    {
        $candidates = $this->getDoctrine()->getRepository(SmartcardPurchase::class)
            ->countPurchasesToRedeem($vendor);

        return $this->json($candidates, 200, [], ['version' => 3]);
    }
}
