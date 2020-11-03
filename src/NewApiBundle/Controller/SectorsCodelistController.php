<?php


namespace NewApiBundle\Controller;


use CommonBundle\Pagination\Paginator;
use ProjectBundle\DBAL\SectorEnum;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;

class SectorsCodelistController extends Controller
{
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

    private static function map(iterable $list): array
    {
        $data = [];
        foreach ($list as $key => $value) {
            $data[] = ['code' => $key, 'value' => $value];
        }

        return $data;
    }

}