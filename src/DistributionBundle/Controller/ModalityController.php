<?php


namespace DistributionBundle\Controller;

use DistributionBundle\Entity\Modality;
use DistributionBundle\Entity\ModalityType;
use DistributionBundle\Utils\ModalityService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class ModalityController
 * @package DistributionBundle\Controller
 */
class ModalityController extends Controller
{
    /** @var SerializerInterface */
    private $serializer;
    /** @var ModalityService */
    private $modalityService;

    /**
     * ModalityController constructor.
     *
     * @param SerializerInterface $serializer
     * @param ModalityService     $modalityService
     */
    public function __construct(SerializerInterface $serializer, ModalityService $modalityService)
    {
        $this->serializer = $serializer;
        $this->modalityService = $modalityService;
    }

    /**
     * @Rest\Get("/modalities")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE') or is_granted('ROLE_DISTRIBUTION_CREATE')")
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
        $all = $this->modalityService->getAll();

        $json = $this->serializer
            ->serialize(
                $all,
                'json',
                ['groups' => ["FullModality"]]
            );

        return new Response($json);
    }

    /**
     * @Rest\Get("/modalities/{id}/types")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE') or is_granted('ROLE_DISTRIBUTION_CREATE')")
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
        $all = $this->modalityService->getAllModalityTypes($modality);

        $json = $this->serializer
            ->serialize(
                $all,
                'json',
                ['groups' => ["FullModalityType"]]
            );

        return new Response($json);
    }
}
