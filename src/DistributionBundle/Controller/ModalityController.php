<?php


namespace DistributionBundle\Controller;


use DistributionBundle\Entity\Modality;
use DistributionBundle\Utils\ModalityService;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class ModalityController extends Controller
{

    /**
     * @Rest\Get("/modalities")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Modalities")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All modalities",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Modality::class))
     *     )
     * )
     *
     * @return Response
     */
    public function getAllAction()
    {
        /** @var ModalityService $modalityService */
        $modalityService = $this->get('distribution.modality_service');
        $all = $modalityService->getAll();

        $json = $this->get('jms_serializer')
            ->serialize(
                $all,
                'json',
                SerializationContext::create()->setGroups(["FullModality"])->setSerializeNull(true)
            );

        return new Response($json);
    }

    /**
     * @Rest\Get("/modalities/{id}/types")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Modalities")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All modalities types",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=ModalityType::class))
     *     )
     * )
     *
     * @param Modality $modality
     * @return Response
     */
    public function getAllModalityTypesAction(Modality $modality)
    {
        /** @var ModalityService $modalityService */
        $modalityService = $this->get('distribution.modality_service');
        $all = $modalityService->getAllModalityTypes($modality);

        $json = $this->get('jms_serializer')
            ->serialize(
                $all,
                'json',
                SerializationContext::create()->setGroups(["FullModalityType"])->setSerializeNull(true)
            );

        return new Response($json);
    }

    /**
     * @Rest\Post("/modalities")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Modalities")
     *
     * @SWG\Response(
     *     response=200,
     *     description="The created modality",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Modality::class))
     *     )
     * )
     *
     * @SWG\Parameter(
     *     name="modality",
     *     in="body",
     *     required=true,
     *     @Model(type=Modality::class, groups={"FullModality"})
     * )
     *
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function createAction(Request $request)
    {
        /** @var ModalityService $modalityService */
        $modalityService = $this->get('distribution.modality_service');
        $created = $modalityService->create($request->request->get('name'));

        $json = $this->get('jms_serializer')
            ->serialize(
                $created,
                'json',
                SerializationContext::create()->setGroups(["FullModality"])->setSerializeNull(true)
            );

        return new Response($json);
    }

    /**
     * @Rest\Post("/modalities/{id}/types")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Modalities")
     *
     * @SWG\Response(
     *     response=200,
     *     description="The created modality type",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=ModalityType::class))
     *     )
     * )
     *
     * @SWG\Parameter(
     *     name="modality type",
     *     in="body",
     *     required=true,
     *     @Model(type=ModalityType::class, groups={"FullModalityType"})
     * )
     *
     * @param Request $request
     * @param Modality $modality
     * @return Response
     * @throws \Exception
     */
    public function createTypeAction(Request $request, Modality $modality)
    {
        /** @var ModalityService $modalityService */
        $modalityService = $this->get('distribution.modality_service');
        $created = $modalityService->createType($modality, $request->request->get('name'));

        $json = $this->get('jms_serializer')
            ->serialize(
                $created,
                'json',
                SerializationContext::create()->setGroups(["FullModalityType"])->setSerializeNull(true)
            );

        return new Response($json);
    }
}