<?php

namespace Controller\VendorApp;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Entity\Vendor;
use Symfony\Component\Serializer\SerializerInterface;

class VendorController extends Controller
{
    /** @var SerializerInterface */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

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
        $json = $this->serializer->serialize($vendor, 'json', ['groups' => ['FullVendor']]);

        return new Response($json);
    }
}
