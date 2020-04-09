<?php

namespace BeneficiaryBundle\Controller;

use BeneficiaryBundle\Utils\ExportCSVService;
use BeneficiaryBundle\Utils\CommunityCSVService;
use BeneficiaryBundle\Utils\CommunityService;
use BeneficiaryBundle\Utils\Mapper\SyriaFileToTemplateMapper;
use CommonBundle\Response\CommonBinaryFileResponse;
use JMS\Serializer\SerializationContext;
use ProjectBundle\Entity\Project;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
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
     * @Rest\Get("/communitys/{id}")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Communitys")
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
        $json = $this->get('jms_serializer')
            ->serialize(
                $community,
                'json',
                SerializationContext::create()->setGroups("FullCommunity")->setSerializeNull(true)
            );
        return new Response($json);
    }

    /**
     * @Rest\Post("/communitys/get/all", name="all_communitys")
     * @ Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Communitys")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All communitys",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Community::class))
     *     )
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function allAction(Request $request)
    {
        $dataOptionRequirements = new OptionsResolver();
        $dataOptionRequirements->setRequired([
            'filter',
            'sort',
            'pageIndex',
            'pageSize',
            '__country',
        ]);
        $dataOptionRequirements->setAllowedTypes('filter', 'array');
        $dataOptionRequirements->setAllowedTypes('sort', 'array');
        $dataOptionRequirements->setAllowedTypes('pageIndex', 'int');
        $dataOptionRequirements->setAllowedTypes('pageSize', 'int');
        $dataOptionRequirements->setAllowedTypes('__country', 'string');
        $dataOptionRequirements->setDefaults([
            'filter' => [],
            'sort' => [],
            'pageIndex' => 0,
            'pageSize' => 10,
            '__country' => 'KHM',
        ]);

        $filters = $dataOptionRequirements->resolve($request->request->all());
        /** @var CommunityService $communityService */
        $communityService = $this->get('beneficiary.community_service');

        try {
            $communitys = $communityService->getAll($filters['__country'], $filters);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $json = $this->get('jms_serializer')
            ->serialize(
                $communitys,
                'json',
                SerializationContext::create()->setGroups("FullCommunity")->setSerializeNull(true)
            );

        return new Response($json);
    }



    /**
     * @Rest\Put("/communitys", name="add_community_projects")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Communitys")
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
     * @param Request $request
     * @return Response
     */
    public function createAction(Request $request)
    {
        $requestArray = $request->request->all();

        $requestRequirements = new OptionsResolver();
        $requestRequirements->setRequired('community');
        $requestRequirements->setAllowedTypes('community', 'array');
        $requestRequirements->setDefaults([
            '__country' => 'KHM',
        ]);

        $requestArray = $requestRequirements->resolve($requestArray);

        /** @var CommunityService $communityService */
        $communityService = $this->get('beneficiary.community_service');
        try {
            $community = $communityService->create($requestArray['__country'], $requestArray['community']);
            $this->getDoctrine()->getManager()->persist($community);
            $this->getDoctrine()->getManager()->flush();
        } catch (ValidationException $exception) {
            return new Response(json_encode(current($exception->getErrors())), Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $json = $this->get('jms_serializer')
            ->serialize(
                $community,
                'json',
                SerializationContext::create()->setGroups(["FullBeneficiary", "FullCommunity"])->setSerializeNull(true)
            );
        return new Response($json);
    }



    /**
     * @Rest\Post("/communitys/{id}", name="edit_community", requirements={"id": "\d+"})
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Communitys")
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
     * @param Request $request
     * @param Community $community
     * @return Response
     */
    public function updateAction(Request $request, Community $community)
    {
        $requestArray = $request->request->all();

        $requestRequirements = new OptionsResolver();
        $requestRequirements->setRequired('community');
        $requestRequirements->setAllowedTypes('community', 'array');
        $requestRequirements->setDefaults([
            '__country' => 'KHM',
        ]);

        $requestArray = $requestRequirements->resolve($requestArray);

        $communityArray = $requestArray['community'];

        /** @var CommunityService $communityService */
        $communityService = $this->get('beneficiary.community_service');
        try {
            $community = $communityService->update($requestArray['__country'], $community, $communityArray);
            $this->getDoctrine()->getManager()->persist($community);
            $this->getDoctrine()->getManager()->flush();
        } catch (ValidationException $exception) {
            return new Response(json_encode(current($exception->getErrors())), Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $json = $this->get('jms_serializer')
            ->serialize(
                $community,
                'json',
                SerializationContext::create()->setGroups(["FullBeneficiary", "FullCommunity"])->setSerializeNull(true)
            );
        return new Response($json);
    }

    /**
     * @Rest\Delete("/communitys/{id}")
     * @Security("is_granted('ROLE_DISTRIBUTIONS_DIRECTOR')")
     *
     * @SWG\Tag(name="Communitys")
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
        $community = $communityService->remove($community);
        $json = $this->get('jms_serializer')
            ->serialize(
                $community,
                'json',
                SerializationContext::create()->setSerializeNull(true)->setGroups(["FullCommunity"])
            );
        return new Response($json);
    }
}
