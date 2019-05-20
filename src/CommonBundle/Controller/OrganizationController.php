<?php

namespace CommonBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class OrganizationController
 * @package OrganizationBundle\Controller
 */
class OrganizationController extends Controller
{

    /**
     * @Rest\Get("/organization", name="get_organization")
     *
     * @SWG\Tag(name="Organization")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="HTTP_BAD_REQUEST"
     * )
     * @param Request $request
     * @return Response
     */
    public function getOrganizationAction(Request $request)
    {        
        try {
            $organization = $this->get('organization_service')->get();
           
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        
        $json = $this->get('jms_serializer')->serialize($organization, 'json', null);
        
        return new Response($json);
    }
}
