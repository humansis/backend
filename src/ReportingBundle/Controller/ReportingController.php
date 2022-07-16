<?php

namespace ReportingBundle\Controller;

use NewApiBundle\Utils\HouseholdExportCSVService;
use CommonBundle\Utils\ExportService;
use phpDocumentor\Reflection\TypeResolver;
use ReportingBundle\Utils\Formatters\Formatter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;

use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

use ReportingBundle\Entity\ReportingIndicator;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class ReportingController
 * @package ReportingBundle\Controller
 */
class ReportingController extends Controller
{

    /**
     * Send formatted data
     * @Rest\Get("/indicators/filtered")
     *
     * @param Request $request
     * @return Response
     */
    public function getFilteredDataAction(Request $request)
    {
        $filters = $request->query->all();

        try {
            $filteredGraphs = $this->get('reporting.reporting_service')->getFilteredData($filters);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), $e->getCode() > 200 ? $e->getCode() : Response::HTTP_BAD_REQUEST);
        }
        return new JsonResponse($filteredGraphs);
    }


    /**
     * Send list of all indicators to display in front
     * @Rest\Post("/indicators")
     *
     * @SWG\Tag(name="Reporting")
     *
     * @SWG\Response(
     *      response=200,
     *          description="Get code reporting",
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @return Response
     */
    public function getAction()
    {
        $indicatorFound = $this->get('reporting.finder')->generateIndicatorsData();
        $json = json_encode($indicatorFound);
        return new Response($json, Response::HTTP_OK);
    }
}
