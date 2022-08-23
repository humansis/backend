<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\Institution;
use CommonBundle\Controller\ExportController;
use CommonBundle\Pagination\Paginator;
use DistributionBundle\Entity\Assistance;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\BookletBatchCreateInputType;
use NewApiBundle\InputType\BookletExportFilterInputType;
use NewApiBundle\InputType\BookletFilterInputType;
use NewApiBundle\InputType\BookletOrderInputType;
use NewApiBundle\InputType\BookletPrintFilterInputType;
use NewApiBundle\InputType\BookletUpdateInputType;
use NewApiBundle\Request\Pagination;
use NewApiBundle\Services\CodeListService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use VoucherBundle\Entity\Booklet;

class BookletController extends AbstractController
{
    /** @var CodeListService */
    private $codeListService;

    public function __construct(CodeListService $codeListService)
    {
        $this->codeListService = $codeListService;
    }
    
    /**
     * @Rest\Get("/web-app/v1/booklets/statuses")
     *
     * @return JsonResponse
     */
    public function statuses(): JsonResponse
    {
        $data = $this->codeListService->mapArray(Booklet::statuses());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/booklets/exports")
     *
     * @param Request                      $request
     * @param BookletExportFilterInputType $inputType
     *
     * @return Response
     */
    public function exports(Request $request, BookletExportFilterInputType $inputType): Response
    {
        $request->query->add([
            'bookletCodes' => true,
        ]);
        $request->request->add([
            '__country' => $request->headers->get('country'),
        ]);

        if ($inputType->hasIds()) {
            $request->request->add(['ids' => $inputType->getIds()]);
        }

        return $this->forward(ExportController::class.'::exportAction', [], $request->query->all());
    }

    /**
     * @Rest\Get("/web-app/v1/booklets/prints")
     *
     * @param BookletPrintFilterInputType $inputType
     *
     * @return Response
     */
    public function bookletPrings(BookletPrintFilterInputType $inputType): Response
    {
        $booklets = $this->getDoctrine()->getRepository(Booklet::class)->findBy(['id' => $inputType->getIds()]);

        return $this->get('voucher.booklet_service')->generatePdf($booklets);
    }

    /**
     * @Rest\Get("/web-app/v1/booklets/{id}")
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
     * @Rest\Put("/web-app/v1/booklets/{id}")
     *
     * @param Booklet                $object
     * @param BookletUpdateInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(Booklet $object, BookletUpdateInputType $inputType): JsonResponse
    {
        $this->get('voucher.booklet_service')->update($object, [
            'currency' => $inputType->getCurrency(),
            'number_vouchers' => $inputType->getQuantityOfVouchers(),
            'password' => $inputType->getPassword(),
            'individual_values' => $inputType->getValues(),
        ]);

        return $this->json($object);
    }

    /**
     * @Rest\Get("/web-app/v1/booklets")
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
     * @Rest\Post("/web-app/v1/booklets/batches")
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
     * @Rest\Delete("/web-app/v1/booklets/{id}")
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

    /**
     * @Rest\Put("/web-app/v1/assistances/{assistanceId}/beneficiaries/{beneficiaryId}/booklets/{bookletCode}")
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
    public function assignToBeneficiary(Assistance $assistance, Beneficiary $beneficiary, Booklet $booklet): JsonResponse
    {
        $this->get('voucher.booklet_service')->assign($booklet, $assistance, $beneficiary);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Put("/web-app/v1/assistances/{assistanceId}/communities/{communityId}/booklets/{bookletCode}")
     * @ParamConverter("assistance", options={"mapping": {"assistanceId" : "id"}})
     * @ParamConverter("community", options={"mapping": {"communityId" : "id"}})
     * @ParamConverter("booklet", options={"mapping": {"bookletCode" : "code"}})
     *
     * @param Assistance $assistance
     * @param Community  $community
     * @param Booklet    $booklet
     *
     * @return JsonResponse
     */
    public function assignToCommunity(Assistance $assistance, Community $community, Booklet $booklet): JsonResponse
    {
        $this->get('voucher.booklet_service')->assign($booklet, $assistance, $community);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Put("/web-app/v1/assistances/{assistanceId}/institutions/{institutionId}/booklets/{bookletCode}")
     * @ParamConverter("assistance", options={"mapping": {"assistanceId" : "id"}})
     * @ParamConverter("institution", options={"mapping": {"institutionId" : "id"}})
     * @ParamConverter("booklet", options={"mapping": {"bookletCode" : "code"}})
     *
     * @param Assistance  $assistance
     * @param Institution $institution
     * @param Booklet     $booklet
     *
     * @return JsonResponse
     */
    public function assignToInstitution(Assistance $assistance, Institution $institution, Booklet $booklet): JsonResponse
    {
        $this->get('voucher.booklet_service')->assign($booklet, $assistance, $institution);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
