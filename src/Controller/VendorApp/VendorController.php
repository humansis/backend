<?php

namespace Controller\VendorApp;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Entity\Vendor;
use Symfony\Component\Serializer\SerializerInterface;

class VendorController extends Controller
{
    public function __construct(private readonly SerializerInterface $serializer)
    {
    }

    /**
     * Get single vendor.
     *
     * @Rest\Get("/vendor-app/v1/vendors/{id}")
     *
     *
     * @return Response
     */
    public function getSingleActionVendor(Vendor $vendor)
    {
        $json = $this->serializer->serialize($vendor, 'json', ['groups' => ['FullVendor']]);

        return new Response($json);
    }
}
