<?php

namespace TransactionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TransactionController extends Controller
{

    /**
     * @Rest\Get("/transaction", name="test_transaction")
     * 
     * @SWG\Tag(name="Common")
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
    public function getTransactionAction(Request $request)  {
        
        try
        {
            $response = $this->get('transaction.transaction_service')->getToken();
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        
        $json = $this->get('jms_serializer')->serialize($response, 'json', null);
        
        return new Response($json);
        
    }

}
