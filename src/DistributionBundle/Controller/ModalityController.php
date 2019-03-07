<?php


namespace DistributionBundle\Controller;


use DistributionBundle\Entity\Modality;
use DistributionBundle\Entity\ModalityType;
use DistributionBundle\Utils\ModalityService;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class ModalityController
 * @package DistributionBundle\Controller
 */
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
}