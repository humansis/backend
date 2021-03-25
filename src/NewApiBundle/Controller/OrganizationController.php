<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Entity\Organization;
use CommonBundle\Entity\OrganizationServices;
use CommonBundle\Repository\OrganizationRepository;
use CommonBundle\Repository\OrganizationServicesRepository;
use CommonBundle\Utils\OrganizationService;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\File\UploadService;
use NewApiBundle\InputType\OrganizationUpdateInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OrganizationController extends AbstractController
{
    /** @var OrganizationService */
    private $organizationService;

    /** @var UploadService */
    private $uploadService;

    /**
     * OrganizationController constructor.
     *
     * @param OrganizationService $organizationService
     */
    public function __construct(OrganizationService $organizationService, UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
        $this->organizationService = $organizationService;
    }

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
     * @Rest\Put("/organizations/{id}")
     *
     * @param Organization                $organization
     * @param OrganizationUpdateInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(Organization $organization, OrganizationUpdateInputType $inputType): JsonResponse
    {
        $this->organizationService->update($organization, $inputType);

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
     * @param Request              $request
     * @param OrganizationServices $organizationServices
     *
     * @return JsonResponse
     */
    public function updateService(Request $request, OrganizationServices $organizationServices): JsonResponse
    {
        if ($request->request->has('enabled')) {
            $this->organizationService->setEnable($organizationServices, $request->request->getBoolean('enabled'));
        }

        if ($request->request->has('parameters')) {
            $this->organizationService->setParameters($organizationServices, $request->request->get('parameters'));
        }

        return $this->json($organizationServices);
    }

    /**
     * @Rest\Post("/organizations/{id}/images")
     *
     * @param Organization $organization
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function uploadImage(Organization $organization, Request $request): JsonResponse
    {
        if (!($file = $request->files->get('file'))) {
            throw new BadRequestHttpException('File missing.');
        }

        if (!in_array($file->getMimeType(), ['image/gif', 'image/jpeg', 'image/png'])) {
            throw new BadRequestHttpException('Invalid file type.');
        }

        $url = $this->uploadService->upload($file, 'organization');

        $organization->setLogo($url);

        $this->getDoctrine()->getManager()->persist($organization);
        $this->getDoctrine()->getManager()->flush();

        return $this->json(['url' => $url]);
    }

}
