<?php
declare(strict_types=1);

namespace ProjectBundle\Controller;

use ProjectBundle\DTO\Sector;
use ProjectBundle\Entity\Project;
use ProjectBundle\Entity\ProjectSector;
use ProjectBundle\Mapper\SectorMapper;
use ProjectBundle\Utils\SectorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
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
    /** @var SectorMapper */
    private $sectorMapper;
    /** @var SectorService */
    private $sectorService;

    /**
     * SectorController constructor.
     *
     * @param SectorMapper  $sectorMapper
     * @param SectorService $sectorService
     */
    public function __construct(SectorMapper $sectorMapper, SectorService $sectorService)
    {
        $this->sectorMapper = $sectorMapper;
        $this->sectorService = $sectorService;
    }

    /**
     * @Rest\Get("/sectors", name="get_all_sectors")
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
        $sectors = $this->sectorService->getSubsBySector();

        return $this->json($this->sectorMapper->listToSubArrays($sectors));
    }

    /**
     * @Rest\Get("/project/{id}/sectors", name="get_project_sectors")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Sectors")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Project Sectors",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Sector::class))
     *     )
     * )
     * @param Project $project
     *
     * @return Response
     */
    public function projectSectors(Project $project)
    {
        $all = $this->sectorService->getSubsBySector();
        $projectSectorDTOs = [];

        /** @var ProjectSector $projectSector */
        foreach ($project->getSectors() as $projectSector) {
            if (isset($all[$projectSector->getSector()])) {
                $projectSectorDTOs[$projectSector->getSector()] = $all[$projectSector->getSector()];
            }
        }
        return $this->json($this->sectorMapper->listToSubArrays($projectSectorDTOs));
    }
}
