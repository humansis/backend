<?php


namespace NewApiBundle\Controller;


use CommonBundle\Pagination\Paginator;
use ProjectBundle\DBAL\SectorEnum;
use ProjectBundle\DTO\Sector;
use ProjectBundle\Utils\SectorService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;

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
        $data = self::map(SectorEnum::all());

        return $this->json(new Paginator($data));
    }


    /**
     * @Rest\Get("/sectors/{code}/subsectors")
     *
     * @param int $code
     *
     * @return JsonResponse
     */
    public function getSubsectors(int $code): JsonResponse
    {
        $sector = SectorEnum::all()[$code];

        $subsectors = $this->sectorService->getSubsBySector()[$sector];

        return $this->json(self::mapSubsectors($subsectors));
    }

    private static function mapSubsectors(iterable $subsectors)
    {
        $data = [];

        /**
         * @var int $key
         * @var Sector $subsector
         */
        foreach ($subsectors as $key => $subsector) {
            $data[] = ['code' => $key, 'value' => $subsector->getSubSectorName()];
        }

        return $data;
    }

    private static function map(iterable $list): array
    {
        $data = [];
        foreach ($list as $key => $value) {
            $data[] = ['code' => $key, 'value' => $value];
        }

        return $data;
    }

}