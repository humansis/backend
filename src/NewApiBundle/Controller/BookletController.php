<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\BookletBatchCreateInputType;
use NewApiBundle\InputType\BookletFilterInputType;
use NewApiBundle\InputType\BookletOrderInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use VoucherBundle\Entity\Booklet;

class BookletController extends AbstractController
{
    /**
     * @Rest\Get("/booklets/{id}")
     *
     * @param Booklet $object
     *
     * @return JsonResponse
     */
    public function item(Booklet $object): JsonResponse
    {
        return $this->json($object);
    }

    /**
     * @Rest\Get("/booklets")
     *
     * @param Request                $request
     * @param BookletFilterInputType $filter
     * @param Pagination             $pagination
     * @param BookletOrderInputType  $orderBy
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

        return $this->json($list);
    }

    /**
     * @Rest\Post("/booklets/batches")
     *
     * @param BookletBatchCreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(BookletBatchCreateInputType $inputType): JsonResponse
    {
        $this->get('voucher.booklet_service')->createBooklets($inputType);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Delete("/booklets/{id}")
     *
     * @param Booklet $object
     *
     * @return JsonResponse
     */
    public function delete(Booklet $object): JsonResponse
    {
        try {
            $deleted = $this->get('voucher.booklet_service')->deleteBookletFromDatabase($object);
        } catch (\Exception $exception) {
            $deleted = false;
        }

        return $this->json(null, $deleted ? Response::HTTP_NO_CONTENT : Response::HTTP_BAD_REQUEST);
    }
}
