<?php

declare(strict_types=1);

namespace Controller\VendorApp;

use Doctrine\Persistence\ManagerRegistry;
use Entity\Assistance\ReliefPackage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Entity\Vendor;

class ReliefPackageController extends AbstractVendorAppController
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }
    /**
     * @Rest\Get("/vendor-app/v1/vendors/{id}/relief-packages")
     *
     *
     */
    public function beneficiaries(Vendor $vendor, Request $request): JsonResponse
    {
        if (!$vendor->canDoRemoteDistributions()) {
            throw $this->createAccessDeniedException(
                "Vendor #{$vendor->getId()} is not allowed for remote distributions."
            );
        }

        $reliefPackages = $this->managerRegistry
            ->getRepository(ReliefPackage::class)
            ->getForVendor($vendor, $this->getCountryCode($request));

        $response = $this->json($reliefPackages);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }
}
