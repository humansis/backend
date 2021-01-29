<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Repository\CommunityRepository;
use BeneficiaryBundle\Utils\CommunityService;
use NewApiBundle\InputType\CommunityCreateInputType;
use NewApiBundle\InputType\CommunityFilterType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * Class CommunityController
 * @package NewApiBundle\Controller
 */
class CommunityController extends AbstractController
{
    /**
     * @Rest\Get("/communities/{id}")
     *
     * @param Community $object
     *
     * @return JsonResponse
     */
    public function item(Community $object): JsonResponse
    {
        return $this->json($object);
    }

    /**
     * @Rest\Get("/communities")
     *
     * @param Request $request
     * @param CommunityFilterType $communityFilterType
     * @param Pagination $pagination
     *
     * @return JsonResponse
     */
    public function list(Request $request, CommunityFilterType $communityFilterType, Pagination $pagination): JsonResponse
    {
        /** @var CommunityRepository $communityRepository */
        $communityRepository = $this->getDoctrine()->getRepository(Community::class);

        $communitiesPerCountry = $communityRepository->findByParams($request->headers->get('country'), $communityFilterType, $pagination);

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
        /** @var CommunityService $object */
        $object = $this->get('beneficiary.community_service');

        $community = $object->createCommunity($inputType);

        return $this->json($community);
    }

    /**
     * @Rest\Put("/projects/{id}")
     *
     * @param Project                $project
     * @param ProjectUpdateInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(Project $project, ProjectUpdateInputType $inputType): JsonResponse
    {
        if ($project->getArchived()) {
            throw new BadRequestHttpException('Unable to update archived project.');
        }

        $object = $this->get('project.project_service')->update($project, $inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Delete("/projects/{id}")
     *
     * @param Project $project
     *
     * @return JsonResponse
     */
    public function delete(Project $project): JsonResponse
    {
        $this->get('project.project_service')->delete($project);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
