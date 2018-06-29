<?php

namespace ReportingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;

use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

class ReportingController extends Controller
{

/**
     * @Rest\Get("/test", name="test")
     */
    public function testAction(Request $request)
    {


        $dataComputed = $this->get('reporting.data_fillers.country')->BMS_Country_TH();
        dump($dataComputed);


    }
}
