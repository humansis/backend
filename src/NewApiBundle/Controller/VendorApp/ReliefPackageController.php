<?php
declare(strict_types=1);

namespace NewApiBundle\Controller\VendorApp;

use NewApiBundle\Entity\ReliefPackage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use VoucherBundle\Entity\Vendor;

class ReliefPackageController extends AbstractVendorAppController
{
    /**
     * @Rest\Get("/vendor-app/v1/vendors/{id}/relief-packages")
     *
     * @param Request $request
     * @param Vendor  $vendor
     *
     * @return JsonResponse
     */
    public function beneficiaries(Vendor $vendor, Request $request): JsonResponse
    {
        if (!$vendor->canDoRemoteDistributions()) {
            $this->createAccessDeniedException("Vendor #{$vendor->getId()} is not allowed for remote distributions.");
        }

        $reliefPackages = $this->getDoctrine()->getRepository(ReliefPackage::class)->getForVendor($vendor);

        $response = $this->json($reliefPackages);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }
}
