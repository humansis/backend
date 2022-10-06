<?php

declare(strict_types=1);

namespace Controller\OfflineApp;

use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use Entity\Assistance;
use Entity\Beneficiary;
use InputType\BookletFilterInputType;
use InputType\BookletOrderInputType;
use Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Entity\Booklet;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class BookletController extends AbstractOfflineAppController
{
    /**
     * @Rest\Get("/offline-app/v1/booklets")
     *
     * @param Request $request
     * @param BookletFilterInputType $filter
     * @param Pagination $pagination
     * @param BookletOrderInputType $orderBy
     *
     * @return JsonResponse
     */
    public function list(Request $request, BookletFilterInputType $filter, Pagination $pagination, BookletOrderInputType $orderBy): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $list = $this->getDoctrine()->getRepository(Booklet::class)
            ->findByParams($countryIso3, $filter, $orderBy, $pagination);

        $response = $this->json($list);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * Assign the booklet to a specific beneficiary.
     *
     * @Rest\Post("/offline-app/v1/booklets/assign/{distributionId}/{beneficiaryId}")
     *
     * @ParamConverter("booklet", options={"mapping": {"bookletId": "code"}})
     * @ParamConverter("assistance", options={"mapping": {"distributionId": "id"}})
     * @ParamConverter("beneficiary", options={"mapping": {"beneficiaryId": "id"}})
     *
     * @param Request $request
     * @param Assistance $assistance
     * @param Beneficiary $beneficiary
     * @return Response
     */
    public function offlineAssignAction(Request $request, Assistance $assistance, Beneficiary $beneficiary)
    {
        $code = $request->request->get('code');
        $booklet = $this->get('voucher.booklet_service')->getOne($code);
        try {
            $return = $this->get('voucher.booklet_service')->assign($booklet, $assistance, $beneficiary);
        } catch (Exception $exception) {
            $this->container->get('logger')->error('exception', [$exception->getMessage()]);

            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(json_encode($return));
    }
}
