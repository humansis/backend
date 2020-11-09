<?php


namespace NewApiBundle\Controller;


use CommonBundle\Pagination\Paginator;
use NewApiBundle\Exception\NotFoundException;
use ProjectBundle\DBAL\SectorEnum;
use ProjectBundle\DTO\Sector;
use ProjectBundle\Utils\SectorService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;

class SectorsCodelistController extends Controller
{
    /** @var SectorService */
    private $sectorService;

    public function __construct(SectorService $sectorService)
    {
        $this->sectorService = $sectorService;
    }

    /**
     * @Rest\Get("/sectors")
     *
     * @return JsonResponse
     */
    public function getSectors(): JsonResponse
    {
        $data = self::mapSectors(SectorEnum::all());

        return $this->json(new Paginator($data));
    }


    /**
     * @Rest\Get("/sectors/{code}/subsectors")
     *
     * @param string $code
     *
     * @return JsonResponse
     */
    public function getSubSectors(string $code): JsonResponse
    {
        try {
            $subSectors = self::mapSubSectors($this->sectorService->findSubsSectorsBySector($code));

            return $this->json(new Paginator($subSectors));
        } catch (NotFoundException $e) {
            return $this->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    private static function mapSubSectors(iterable $subSectors)
    {
        $data = [];

        /** @var Sector $subSector */
        foreach ($subSectors as $subSector) {
            $data[] = ['code' => $subSector->getSubSectorName(), 'value' => $subSector->getSubSectorName()];
        }

        return $data;
    }

    private static function mapSectors(iterable $list): array
    {
        $data = [];
        foreach ($list as $value) {
            $data[] = ['code' => $value, 'value' => $value];
        }

        return $data;
    }

}