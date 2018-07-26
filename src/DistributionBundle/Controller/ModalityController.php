<?php


namespace DistributionBundle\Controller;


use DistributionBundle\Entity\Modality;
use DistributionBundle\Utils\ModalityService;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ModalityController extends Controller
{

    /**
     * @Rest\Get("/modalities")
     * @return Response
     */
    public function getAllAction()
    {
        /** @var ModalityService $modalityService */
        $modalityService = $this->get('distribution.modality_service');
        $all = $modalityService->getAll();

        $json = $this->get('jms_serializer')
            ->serialize($all,
                'json',
                SerializationContext::create()->setGroups(["FullModality"])->setSerializeNull(true)
                );

        return new Response($json);
    }

    /**
     * @Rest\Get("/modalities/{id}/types")
     * @param Modality $modality
     * @return Response
     */
    public function getAllModalityTypesAction(Modality $modality)
    {
        /** @var ModalityService $modalityService */
        $modalityService = $this->get('distribution.modality_service');
        $all = $modalityService->getAllModalityTypes($modality);

        $json = $this->get('jms_serializer')
            ->serialize($all,
                'json',
                SerializationContext::create()->setGroups(["FullModalityType"])->setSerializeNull(true)
                );

        return new Response($json);
    }

    /**
     * @Rest\Post("/modalities")
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
            ->serialize($created,
                'json',
                SerializationContext::create()->setGroups(["FullModality"])->setSerializeNull(true)
            );

        return new Response($json);
    }

    /**
     * @Rest\Post("/modalities/{id}/types")
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
            ->serialize($created,
                'json',
                SerializationContext::create()->setGroups(["FullModalityType"])->setSerializeNull(true)
            );

        return new Response($json);
    }
}