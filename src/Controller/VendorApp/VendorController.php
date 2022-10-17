<?php

namespace Controller\VendorApp;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Entity\Vendor;

class VendorController extends Controller
{
    /**
     * Get single vendor.
     *
     * @Rest\Get("/vendor-app/v1/vendors/{id}")
     *
     * @param Vendor $vendor
     *
     * @return Response
     */
    public function getSingleActionVendor(Vendor $vendor)
    {
        $json = $this->get('serializer')->serialize($vendor, 'json', ['groups' => ['FullVendor']]);

        return new Response($json);
    }
}
