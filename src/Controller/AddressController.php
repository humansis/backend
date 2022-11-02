<?php

namespace Controller;

use Entity\Address;
use Entity\HouseholdLocation;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\AddressFilterInputType;
use InputType\CampAddressFilterInputType;
use InputType\ResidenceAddressFilterInputType;
use InputType\TemporarySettlementAddressFilterInputType;
use Symfony\Component\HttpFoundation\JsonResponse;

class AddressController extends AbstractController
{
    /**
     * @Rest\Get("/web-app/v1/addresses/camps")
     *
     *
     */
    public function camps(CampAddressFilterInputType $filter): JsonResponse
    {
        $campAddresses = $this->getDoctrine()->getRepository(HouseholdLocation::class)->findCampAddressesByParams(
            $filter
        );

        return $this->json($campAddresses);
    }

    /**
     * @Rest\Get("/web-app/v1/addresses/camps/{id}")
     *
     *
     */
    public function camp(HouseholdLocation $campAddress): JsonResponse
    {
        if (HouseholdLocation::LOCATION_TYPE_CAMP !== $campAddress->getType()) {
            throw $this->createNotFoundException();
        }

        return $this->json($campAddress);
    }

    /**
     * @Rest\Get("/web-app/v1/addresses/residencies")
     *
     *
     */
    public function residences(ResidenceAddressFilterInputType $filter): JsonResponse
    {
        $residences = $this->getDoctrine()->getRepository(HouseholdLocation::class)->findResidenciesByParams($filter);

        return $this->json($residences);
    }

    /**
     * @Rest\Get("/web-app/v1/addresses/residencies/{id}")
     *
     *
     */
    public function residence(HouseholdLocation $residence): JsonResponse
    {
        if (HouseholdLocation::LOCATION_TYPE_RESIDENCE !== $residence->getType()) {
            throw $this->createNotFoundException();
        }

        return $this->json($residence);
    }

    /**
     * @Rest\Get("/web-app/v1/addresses/temporary-settlements")
     *
     *
     */
    public function temporarySettlements(TemporarySettlementAddressFilterInputType $filter): JsonResponse
    {
        $temporarySettlements = $this->getDoctrine()->getRepository(
            HouseholdLocation::class
        )->findTemporarySettlementsByParams($filter);

        return $this->json($temporarySettlements);
    }

    /**
     * @Rest\Get("/web-app/v1/addresses/temporary-settlements/{id}")
     *
     *
     */
    public function temporarySettlement(HouseholdLocation $temporarySettlement): JsonResponse
    {
        if (HouseholdLocation::LOCATION_TYPE_SETTLEMENT !== $temporarySettlement->getType()) {
            throw $this->createNotFoundException();
        }

        return $this->json($temporarySettlement);
    }

    /**
     * @Rest\Get("/web-app/v1/addresses")
     *
     *
     */
    public function addresses(AddressFilterInputType $filter): JsonResponse
    {
        $temporarySettlements = $this->getDoctrine()->getRepository(Address::class)->findByParams($filter);

        return $this->json($temporarySettlements);
    }

    /**
     * @Rest\Get("/web-app/v1/addresses/{id}")
     *
     *
     */
    public function address(Address $address): JsonResponse
    {
        return $this->json($address);
    }
}
