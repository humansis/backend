<?php


namespace DistributionBundle\Controller;


use DistributionBundle\Entity\Modality;
use DistributionBundle\Utils\ModalityService;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;

class ModalityController extends Controller
{

    /**
     * @Rest\Get("/modalities")
     * @return Response
     */
    public function getAll()
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
    public function getAllModalityTypes(Modality $modality)
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
}