<?php


namespace DistributionBundle\Controller;


use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Utils\DistributionCSVService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DistributionCSVController extends Controller
{

    /**
     * @Rest\Get("/distributions/{id}/export", name="export_csv")
     *
     * @param Request $request
     * @param DistributionData $distributionData
     * @return Response
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportAction(Request $request, DistributionData $distributionData)
    {
        /** @var DistributionCSVService $distributionCSVService */
        $distributionCSVService = $this->get('distribution.distribution_csv_service');
        $distributionCSVService->export($request->request->get('__country'), $distributionData);

        return new Response(json_encode($distributionCSVService));
    }
}