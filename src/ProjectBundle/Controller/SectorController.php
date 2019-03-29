<?php


namespace ProjectBundle\Controller;

use JMS\Serializer\SerializationContext;
use ProjectBundle\Entity\Sector;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class SectorController
 * @package ProjectBundle\Controller
 */
class SectorController extends Controller
{

    /**
     * @Rest\Get("/sectors", name="get_all_sectors")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Sectors")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All Sectors",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Sector::class))
     *     )
     * )
     * @return Response
     */
    public function getAllAction()
    {
        $sectors = $this->get('project.sector_service')->findAll();

        $json = $this->get('jms_serializer')
            ->serialize($sectors, 'json', SerializationContext::create()->setGroups(['FullSector'])->setSerializeNull(true));

        return new Response($json);
    }
}
