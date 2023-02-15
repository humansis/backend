<?php

declare(strict_types=1);

namespace Controller;

use Doctrine\Persistence\ManagerRegistry;
use Entity\Community;
use Repository\CommunityRepository;
use Utils\CommunityService;
use Entity\Assistance;
use InputType\AssistanceCommunitiesFilterInputType;
use InputType\CommunityCreateInputType;
use InputType\CommunityFilterType;
use InputType\CommunityOrderInputType;
use InputType\CommunityUpdateInputType;
use Request\Pagination;
use Entity\Project;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CommunityController
 *
 * @package Controller
 */
class CommunityController extends AbstractController
{
    public function __construct(private readonly CommunityService $communityService, private readonly ManagerRegistry $managerRegistry)
    {
    }

    #[Rest\Get('/web-app/v1/communities/{id}')]
    public function item(Community $object): JsonResponse
    {
        if (true === $object->getArchived()) {
            throw $this->createNotFoundException();
        }

        return $this->json($object);
    }

    #[Rest\Get('/web-app/v1/communities')]
    public function list(
        Request $request,
        CommunityOrderInputType $communityOrderInputType,
        CommunityFilterType $communityFilterType,
        Pagination $pagination
    ): JsonResponse {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        /** @var CommunityRepository $communityRepository */
        $communityRepository = $this->managerRegistry->getRepository(Community::class);

        $communitiesPerCountry = $communityRepository->findByParams(
            $request->headers->get('country'),
            $communityOrderInputType,
            $communityFilterType,
            $pagination
        );

        return $this->json($communitiesPerCountry);
    }

    #[Rest\Post('/web-app/v1/communities')]
    public function create(CommunityCreateInputType $inputType): JsonResponse
    {
        $community = $this->communityService->create($inputType);

        return $this->json($community);
    }

    #[Rest\Put('/web-app/v1/communities/{id}')]
    public function update(Community $community, CommunityUpdateInputType $inputType): JsonResponse
    {
        $object = $this->communityService->update($community, $inputType);

        return $this->json($object);
    }

    #[Rest\Delete('/web-app/v1/communities/{id}')]
    public function delete(Community $project): JsonResponse
    {
        $this->communityService->remove($project);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Rest\Get('/web-app/v1/projects/{id}/communities')]
    public function communitiesByProject(Project $project): JsonResponse
    {
        $communities = $this->managerRegistry->getRepository(Community::class)->findByProject($project);

        return $this->json($communities);
    }
}
