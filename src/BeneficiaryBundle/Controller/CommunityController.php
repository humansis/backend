<?php

namespace BeneficiaryBundle\Controller;

use BeneficiaryBundle\InputType\UpdateCommunityType;
use BeneficiaryBundle\Mapper\CommunityMapper;
use BeneficiaryBundle\Utils\CommunityService;
use CommonBundle\InputType\Country;
use CommonBundle\InputType\DataTableType;

use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BeneficiaryBundle\Entity\Community;

//Annotations
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Throwable;

class CommunityController extends Controller
{
    /**
     * @Rest\Get("/communities/{id}")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Communities")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Community $community
     * @return Response
     */
    public function showAction(Community $community)
    {
        if (true === $community->getArchived()) {
            return new Response("Community was archived", Response::HTTP_NOT_FOUND);
        }

        /** @var CommunityMapper $communityMapper */
        $communityMapper = $this->get(CommunityMapper::class);
        return $this->json($communityMapper->toFullArray($community));
    }

    /**
     * @Rest\Post("/communities/get/all", name="all_communities")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Communities")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All communities",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Community::class))
     *     )
     * )
     *
     * @param Country $country
     * @param DataTableType $dataTableType
     * @return Response
     */
    public function allAction(Country $country, DataTableType $dataTableType)
    {
        /** @var CommunityService $communityService */
        $communityService = $this->get('beneficiary.community_service');
        /** @var CommunityMapper $communityMapper */
        $communityMapper = $this->get(CommunityMapper::class);

        try {
            $communities = $communityService->getAll($country, $dataTableType);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            0 => $communities[0],
            1 => $communityMapper->toFullArrays($communities[1])
        ]);
    }


    /**
     * @Rest\Put("/communities", name="add_community_projects")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Communities")
     *
     * @SWG\Parameter(
     *     name="community",
     *     in="body",
     *     required=true,
     *     @Model(type=Community::class, groups={"FullCommunity"})
     * )
     *
     * @SWG\Parameter(
     *     name="projects",
     *     in="body",
     *     required=true,
     *     type="array",
     *     schema={}
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Community created",
     *     @Model(type=Community::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     *
     * @param Country $country
     * @param UpdateCommunityType $communityType
     * @return Response
     */
    public function createAction(Country $country, UpdateCommunityType $communityType)
    {
        /** @var CommunityService $communityService */
        $communityService = $this->get('beneficiary.community_service');
        /** @var CommunityMapper $communityMapper */
        $communityMapper = $this->get(CommunityMapper::class);
        try {
            $community = $communityService->create($country, $communityType);
            $this->getDoctrine()->getManager()->persist($community);
            $this->getDoctrine()->getManager()->flush();
        } catch (ValidationException $exception) {
            return new Response(json_encode(current($exception->getErrors())), Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json($communityMapper->toFullArray($community));
    }


    /**
     * @Rest\Post("/communities/{id}", name="edit_community", requirements={"id": "\d+"})
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Communities")
     *
     * @SWG\Parameter(
     *     name="community",
     *     in="body",
     *     required=true,
     *     @Model(type=Community::class, groups={"FullCommunity"})
     * )
     *
     * @SWG\Parameter(
     *     name="projects",
     *     in="body",
     *     required=true,
     *     type="array",
     *     schema={}
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Community edited",
     *     @Model(type=Community::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     *
     * @param Country $country
     * @param Community $community
     * @param UpdateCommunityType $communityType
     * @return Response
     */
    public function updateAction(Country $country, Community $community, UpdateCommunityType $communityType)
    {
        /** @var CommunityService $communityService */
        $communityService = $this->get('beneficiary.community_service');
        /** @var CommunityMapper $communityMapper */
        $communityMapper = $this->get(CommunityMapper::class);
        try {
            $community = $communityService->update($country, $community, $communityType);
            $this->getDoctrine()->getManager()->persist($community);
            $this->getDoctrine()->getManager()->flush();
        } catch (ValidationException $exception) {
            return new Response(json_encode(current($exception->getErrors())), Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json($communityMapper->toFullArray($community));
    }

    /**
     * @Rest\Delete("/communities/{id}")
     * @Security("is_granted('ROLE_DISTRIBUTIONS_DIRECTOR')")
     *
     * @SWG\Tag(name="Communities")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Community $community
     * @return Response
     */
    public function deleteAction(Community $community)
    {
        /** @var CommunityService $communityService */
        $communityService = $this->get("beneficiary.community_service");
        /** @var CommunityMapper $communityMapper */
        $communityMapper = $this->get(CommunityMapper::class);
        $community = $communityService->remove($community);
        return $this->json($communityMapper->toFullArray($community));
    }
}
