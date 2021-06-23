<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Repository\CommunityRepository;
use BeneficiaryBundle\Utils\CommunityService;
use DistributionBundle\Entity\Assistance;
use NewApiBundle\InputType\AssistanceCommunitiesFilterInputType;
use NewApiBundle\InputType\CommunityCreateInputType;
use NewApiBundle\InputType\CommunityFilterType;
use NewApiBundle\InputType\CommunityOrderInputType;
use NewApiBundle\InputType\CommunityUpdateInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;
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
    /**
     * @Rest\Get("/web-app/v1/communities/{id}")
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
     * @Rest\Get("/web-app/v1/communities")
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
     * @Rest\Post("/web-app/v1/communities")
     *
     * @param CommunityCreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(CommunityCreateInputType $inputType): JsonResponse
    {
        /** @var CommunityService $object */
        $object = $this->get('beneficiary.community_service');

        $community = $object->create($inputType);

        return $this->json($community);
    }

    /**
     * @Rest\Put("/web-app/v1/communities/{id}")
     *
     * @param Community                $community
     * @param CommunityUpdateInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(Community $community, CommunityUpdateInputType $inputType): JsonResponse
    {
        $object = $this->get('beneficiary.community_service')->update($community, $inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Delete("/web-app/v1/communities/{id}")
     *
     * @param Community $project
     *
     * @return JsonResponse
     */
    public function delete(Community $project): JsonResponse
    {
        $this->get('beneficiary.community_service')->remove($project);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Get("/web-app/v1/projects/{id}/communities")
     *
     * @param Project $project
     *
     * @return JsonResponse
     */
    public function communitiesByProject(Project $project): JsonResponse
    {
        $communities = $this->getDoctrine()->getRepository(Community::class)->findByProject($project);

        return $this->json($communities);
    }
}
