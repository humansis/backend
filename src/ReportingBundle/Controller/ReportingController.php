<?php

namespace ReportingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;

use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

use ReportingBundle\Entity\ReportingIndicator;

class ReportingController extends Controller
{

     /**
      * Send data formatted corresponding to code to display it in front
     * @Rest\Post("/indicators/serve/{id}")
     * 
     * @param ReportingIndicator $indicator
     * @param Request $request
     * @return Response
     */
    public function serveAction(Request $request, ReportingIndicator $indicator)
    {
        $filters = $request->request->get('filters');
        try {   
            $dataComputed = $this->get('reporting.computer')->compute($indicator, $filters);
            // $dataFormatted = $this->get('ra_reporting.formatter')->format($dataComputed, $indicator->getGraphique());
        }
        catch (\Exception $e)
        {
            return new Response($e->getMessage(), $e->getCode() > 200 ? $e->getCode() : Response::HTTP_BAD_REQUEST);
        }
        return new Response($dataComputed, Response::HTTP_OK);   
    }


     /**
      * Send list of all indicators to display in front
     * @Rest\Get("/indicators")
     * 
     * @param Request $request
     * @return Response
     */
    public function getAction(Request $request)
    {
        $indicatorFinded = [];
        // $indicatorFinded = $this->get('ra_reporting.finder')->findIndicator();
        $json = json_encode($indicatorFinded);
        return new Response($json, Response::HTTP_OK);
        
    }
}
