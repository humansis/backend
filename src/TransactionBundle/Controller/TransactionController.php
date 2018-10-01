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

use DistributionBundle\Entity\DistributionData;

/**
 * Class TransactionController
 * @package TransactionBundle\Controller
 */
class TransactionController extends Controller
{

    /**
     * Send moeny to distribution beneficiaries via country financial provider
     * @Rest\Post("/transaction/distribution/{id}/send", name="send_money_for_distribution")
     * 
     * @SWG\Tag(name="Transaction")
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
     *
     * @param Request $request
     * @param DistributionData $distributionData
     * @return Response
     */
    public function postTransactionAction(Request $request, DistributionData $distributionData)  {
        $countryISO3 = $request->request->get('__country');
        
        try
        {
            $response = $this->get('transaction.transaction_service')->sendMoney($countryISO3, $distributionData);
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        dump($response);
        $json = $this->get('jms_serializer')->serialize($response, 'json');
        dump($json);
        return new Response($json);
        
    }

}
