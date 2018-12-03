<?php
/**
 * Created by PhpStorm.
 * User: developer3
 * Date: 3/12/18
 * Time: 11:19
 */

namespace CommonBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class LogController extends Controller
{

    /**
     * @Rest\Put("/log", name="log_data")
     *
     * @SWG\Tag(name="Log")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @SWG\Response(
     *     response=204,
     *     description="HTTP_NO_CONTENT"
     * )
     *
     *
     * @param Request $request
     * @return Response|JsonResponse
     */
    public function logAction(Request $request)
    {
        $url = $request->request->get('url');

        try {
            $logService = $this->get('log_service');
            $response = $logService->logData($url);
            return $response;
        } catch (\Exception $exception) {
            return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
        }
    }
}