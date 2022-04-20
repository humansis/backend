<?php declare(strict_types=1);

namespace NewApiBundle\Controller\VendorApp;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Utils\VendorService;

class VendorController extends AbstractVendorAppController
{
    /**
     * Get single vendor.
     *
     * @Rest\Get("/vendor-app/v1/vendors/{id}")
     * @Security("is_granted('ROLE_USER')")
     *
     * @param Vendor $vendor
     *
     * @return Response
     */
    public function vendor(Vendor $vendor)
    {
        return $this->json($vendor);
    }

}
