<?php

declare(strict_types=1);

namespace Controller;

use Entity\Beneficiary;
use Entity\Community;
use Entity\Institution;
use Entity\Voucher;
use Exception;
use InputType\Country;
use InputType\DataTableType;
use InputType\RequestConverter;
use Pagination\Paginator;
use Entity\Assistance;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\BookletBatchCreateInputType;
use InputType\BookletExportFilterInputType;
use InputType\BookletFilterInputType;
use InputType\BookletOrderInputType;
use InputType\BookletPrintFilterInputType;
use InputType\BookletUpdateInputType;
use Repository\BookletRepository;
use Repository\VoucherRepository;
use Request\Pagination;
use Services\CodeListService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Entity\Booklet;
use Utils\BookletService;
use Utils\ExportTableServiceInterface;
use Utils\VoucherService;
use Utils\VoucherTransformData;

class BookletController extends AbstractController
{
    public function __construct(
        private readonly BookletService $bookletService,
        private readonly CodeListService $codeListService,
        private readonly VoucherService $voucherService,
        private readonly VoucherTransformData $voucherTransformData,
        private readonly ExportTableServiceInterface $exportTableService,
        private readonly VoucherRepository $voucherRepository,
        private readonly BookletRepository $bookletRepository
    ) {
    }

    #[Rest\Get('/web-app/v1/booklets/statuses')]
    public function statuses(): JsonResponse
    {
        $data = $this->codeListService->mapArray(Booklet::statuses());

        return $this->json(new Paginator($data));
    }

    #[Rest\Get('/web-app/v1/booklets/exports')]
    public function exports(Request $request, BookletExportFilterInputType $inputType): Response
    {
        $countryIso3 = $request->headers->get("country");
        $filters = $request->request->get('filters');
        $type = $request->query->get('type');

        if ($inputType->hasIds()) {
            $ids = $inputType->getIds();
            $allVouchers = $this->voucherRepository->getAllByBookletIds($ids)->getResult();
        } else {
            if ($filters) {
                /** @var DataTableType $dataTableFilter */
                $dataTableFilter = RequestConverter::normalizeInputType($filters, DataTableType::class);
                $booklets = $this->bookletService->getAll(new Country($countryIso3), $dataTableFilter)[1];
            } else {
                $booklets = $this->bookletRepository->getActiveBooklets($countryIso3);
            }
            $allVouchers = $this->voucherRepository->getAllByBooklets($booklets)->getResult();
        }

        if ($type == 'pdf') {
            return $this->voucherService->exportToPdf($allVouchers);
        } else {
            $exportableTable = $this->voucherTransformData->transformData($allVouchers);
            return $this->exportTableService->export($exportableTable, 'bookletCodes', $type);
        }
    }

    #[Rest\Get('/web-app/v1/booklets/prints')]
    public function bookletPrings(BookletPrintFilterInputType $inputType): Response
    {
        $booklets = $this->bookletRepository->findBy(['id' => $inputType->getIds()]);

        return $this->bookletService->generatePdf($booklets);
    }

    #[Rest\Get('/web-app/v1/booklets/{id}')]
    public function item(Booklet $object): JsonResponse
    {
        return $this->json($object);
    }

    #[Rest\Put('/web-app/v1/booklets/{id}')]
    public function update(Booklet $object, BookletUpdateInputType $inputType): JsonResponse
    {
        $this->bookletService->update($object, [
            'currency' => $inputType->getCurrency(),
            'number_vouchers' => $inputType->getQuantityOfVouchers(),
            'password' => $inputType->getPassword(),
            'individual_values' => $inputType->getValues(),
        ]);

        return $this->json($object);
    }

    #[Rest\Get('/web-app/v1/booklets')]
    public function list(
        Request $request,
        BookletFilterInputType $filter,
        Pagination $pagination,
        BookletOrderInputType $orderBy
    ): JsonResponse {
        $countryIso3 = $request->headers->get('country');
        if (is_null($countryIso3)) {
            throw new BadRequestHttpException('Missing country header');
        }

        $list = $this->bookletRepository->findByParams($countryIso3, $filter, $orderBy, $pagination);

        return $this->json($list);
    }

    #[Rest\Post('/web-app/v1/booklets/batches')]
    public function create(BookletBatchCreateInputType $inputType): JsonResponse
    {
        $this->bookletService->createBooklets($inputType);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Rest\Delete('/web-app/v1/booklets/{id}')]
    public function delete(Booklet $object): JsonResponse
    {
        try {
            $deleted = $this->bookletService->deleteBookletFromDatabase($object);
        } catch (Exception) {
            $deleted = false;
        }

        return $this->json(null, $deleted ? Response::HTTP_NO_CONTENT : Response::HTTP_BAD_REQUEST);
    }

    #[Rest\Put('/web-app/v1/assistances/{assistanceId}/beneficiaries/{beneficiaryId}/booklets/{bookletCode}')]
    #[ParamConverter('assistance', options: ['mapping' => ['assistanceId' => 'id']])]
    #[ParamConverter('beneficiary', options: ['mapping' => ['beneficiaryId' => 'id']])]
    #[ParamConverter('booklet', options: ['mapping' => ['bookletCode' => 'code']])]
    public function assignToBeneficiary(
        Assistance $assistance,
        Beneficiary $beneficiary,
        Booklet $booklet
    ): JsonResponse {
        $this->bookletService->assign($booklet, $assistance, $beneficiary);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Rest\Put('/web-app/v1/assistances/{assistanceId}/communities/{communityId}/booklets/{bookletCode}')]
    #[ParamConverter('assistance', options: ['mapping' => ['assistanceId' => 'id']])]
    #[ParamConverter('community', options: ['mapping' => ['communityId' => 'id']])]
    #[ParamConverter('booklet', options: ['mapping' => ['bookletCode' => 'code']])]
    public function assignToCommunity(Assistance $assistance, Community $community, Booklet $booklet): JsonResponse
    {
        $this->bookletService->assign($booklet, $assistance, $community);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Rest\Put('/web-app/v1/assistances/{assistanceId}/institutions/{institutionId}/booklets/{bookletCode}')]
    #[ParamConverter('assistance', options: ['mapping' => ['assistanceId' => 'id']])]
    #[ParamConverter('institution', options: ['mapping' => ['institutionId' => 'id']])]
    #[ParamConverter('booklet', options: ['mapping' => ['bookletCode' => 'code']])]
    public function assignToInstitution(
        Assistance $assistance,
        Institution $institution,
        Booklet $booklet
    ): JsonResponse {
        $this->bookletService->assign($booklet, $assistance, $institution);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
