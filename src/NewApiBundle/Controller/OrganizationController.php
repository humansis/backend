<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Entity\Organization;
use CommonBundle\Entity\OrganizationServices;
use CommonBundle\Repository\OrganizationRepository;
use CommonBundle\Repository\OrganizationServicesRepository;
use CommonBundle\Utils\OrganizationService;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\OrganizationServicesInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;

class OrganizationController extends AbstractController
{
    /**
     * @Rest\Get("/organizations/{id}")
     *
     * @param Organization $organization
     *
     * @return JsonResponse
     */
    public function item(Organization $organization): JsonResponse
    {
        return $this->json($organization);
    }

    /**
     * @Rest\Get("/organizations")
     *
     * @param Pagination $pagination
     *
     * @return JsonResponse
     */
    public function list(Pagination $pagination): JsonResponse
    {
        /** @var OrganizationRepository $organizationRepository */
        $organizationRepository = $this->getDoctrine()->getRepository(Organization::class);

        $organizations = $organizationRepository->findByParams($pagination);

        return $this->json($organizations);
    }

    /**
     * @Rest\Get("/organizations/{id}/services")
     *
     * @param Organization $organization
     * @param Pagination   $pagination
     *
     * @return JsonResponse
     */
    public function listServices(Organization $organization, Pagination $pagination): JsonResponse
    {
        /** @var OrganizationServicesRepository $organizationRepository */
        $organizationRepository = $this->getDoctrine()->getRepository(OrganizationServices::class);

        $organizationServices = $organizationRepository->findByOrganization($organization, $pagination);

        return $this->json($organizationServices);
    }

    /**
     * @Rest\Patch("/organizations/services/{id}")
     *
     * @param OrganizationServices          $organizationServices
     * @param OrganizationServicesInputType $inputType
     *
     * @return JsonResponse
     */
    public function updateService(OrganizationServices $organizationServices, OrganizationServicesInputType $inputType): JsonResponse
    {
        /** @var OrganizationService $organizationService */
        $organizationService = $this->get('organization_service');

        $updatedOrganizationServices = $organizationService->updateOrganizationServices($organizationServices, $inputType);

        return $this->json($updatedOrganizationServices);
    }


}