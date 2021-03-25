<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Pagination\Paginator;
use DistributionBundle\Entity\Assistance;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\BookletBatchCreateInputType;
use NewApiBundle\InputType\BookletFilterInputType;
use NewApiBundle\InputType\BookletOrderInputType;
use NewApiBundle\Request\Pagination;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use NewApiBundle\Utils\CodeLists;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Utils\BookletService;

class BookletController extends AbstractController
{
    /** @var BookletService */
    private $bookletService;
    /** @var EventDispatcherInterface */
    private $eventDispatcher;
    /** @var LoggerInterface */
    private $logger;

    /**
     * BookletController constructor.
     *
     * @param BookletService           $bookletService
     * @param EventDispatcherInterface $eventDispatcher
     * @param LoggerInterface          $logger
     */
    public function __construct(BookletService $bookletService, EventDispatcherInterface $eventDispatcher, LoggerInterface $logger)
    {
        $this->bookletService = $bookletService;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    /**
     * @Rest\Get("/booklets/statuses")
     *
     * @return JsonResponse
     */
    public function statuses(): JsonResponse
    {
        $data = CodeLists::mapArray(Booklet::statuses());

        return $this->json(new Paginator($data));
    }

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
        $this->bookletService->createBooklets($inputType);

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
            $deleted = $this->bookletService->deleteBookletFromDatabase($object);
        } catch (\Exception $exception) {
            $deleted = false;
        }

        return $this->json(null, $deleted ? Response::HTTP_NO_CONTENT : Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Rest\Put("/assistances/{assistanceId}/beneficiaries/{beneficiaryId}/booklets/{bookletCode}")
     * @ParamConverter("assistance", options={"mapping": {"assistanceId" : "id"}})
     * @ParamConverter("beneficiary", options={"mapping": {"beneficiaryId" : "id"}})
     * @ParamConverter("booklet", options={"mapping": {"bookletCode" : "code"}})
     *
     * @param Assistance  $assistance
     * @param Beneficiary $beneficiary
     * @param Booklet     $booklet
     *
     * @return JsonResponse
     */
    public function assign(Assistance $assistance, Beneficiary $beneficiary, Booklet $booklet): JsonResponse
    {
        try {
            $this->bookletService->assign($booklet, $assistance, $beneficiary);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
