<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Repository\CommunityRepository;
use BeneficiaryBundle\Utils\CommunityService;
use NewApiBundle\InputType\CommunityCreateInputType;
use NewApiBundle\InputType\CommunityFilterType;
use NewApiBundle\InputType\CommunityOrderInputType;
use NewApiBundle\InputType\CommunityUpdateInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CommunityController
 * @package NewApiBundle\Controller
 */
class CommunityController extends AbstractController
{
    /** @var CommunityService */
    private $communityService;

    /**
     * CommunityController constructor.
     *
     * @param CommunityService $communityService
     */
    public function __construct(CommunityService $communityService)
    {
        $this->communityService = $communityService;
    }

    /**
     * @Rest\Get("/communities/{id}")
     *
     * @param Community $object
     *
     * @return JsonResponse
     */
    public function item(Community $object): JsonResponse
    {
        if (true === $object->getArchived()) {
            throw $this->createNotFoundException();
        }

        return $this->json($object);
    }

    /**
     * @Rest\Get("/communities")
     *
     * @param Request $request
     * @param CommunityOrderInputType $communityOrderInputType
     * @param CommunityFilterType $communityFilterType
     * @param Pagination $pagination
     *
     * @return JsonResponse
     */
    public function list(Request $request, CommunityOrderInputType $communityOrderInputType, CommunityFilterType $communityFilterType, Pagination $pagination): JsonResponse
    {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        /** @var CommunityRepository $communityRepository */
        $communityRepository = $this->getDoctrine()->getRepository(Community::class);

        $communitiesPerCountry = $communityRepository->findByParams($request->headers->get('country'), $communityOrderInputType, $communityFilterType, $pagination);

        return $this->json($communitiesPerCountry);
    }

    /**
     * @Rest\Post("/communities")
     *
     * @param CommunityCreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(CommunityCreateInputType $inputType): JsonResponse
    {
        $community = $this->communityService->create($inputType);

        return $this->json($community);
    }

    /**
     * @Rest\Put("/communities/{id}")
     *
     * @param Community                $community
     * @param CommunityUpdateInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(Community $community, CommunityUpdateInputType $inputType): JsonResponse
    {
        $object = $this->communityService->update($community, $inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Delete("/communities/{id}")
     *
     * @param Community $project
     *
     * @return JsonResponse
     */
    public function delete(Community $project): JsonResponse
    {
        $this->communityService->remove($project);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
