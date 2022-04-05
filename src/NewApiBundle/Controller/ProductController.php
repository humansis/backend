<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Entity\Organization;
use DistributionBundle\Enum\AssistanceTargetType;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\File\UploadService;
use NewApiBundle\InputType\ProductCreateInputType;
use NewApiBundle\InputType\ProductFilterInputType;
use NewApiBundle\InputType\ProductOrderInputType;
use NewApiBundle\InputType\ProductUpdateInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use VoucherBundle\Entity\Product;
use VoucherBundle\Repository\ProductRepository;

class ProductController extends AbstractController
{
    /** @var UploadService */
    private $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    /**
     * @Rest\Get("/web-app/v1/products/exports")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exports(Request $request): Response
    {
        $request->query->add([
            'products' => true,
        ]);
        $request->request->add([
            '__country' => $request->headers->get('country'),
        ]);

        return $this->legacyExport($request);
    }

    /**
     * @deprecated copied from old ExportController, needs to be rewritten
     * @param Request $request
     *
     * @return Response
     */
    private function legacyExport(Request $request): Response
    {
        try {
            set_time_limit(600);
            // Format of the file (csv, xlsx, ods, pdf)
            $type = $request->query->get('type');
            // Generate corresponding file depending on request
            if ($request->query->get('distributions')) {
                $idProject = $request->query->get('distributions');
                if ($type === 'pdf') {
                    return $this->get('distribution.assistance_service')->exportToPdf($idProject);
                }
                $filename = $this->get('distribution.assistance_service')->exportToCsv($idProject, $type);
            } elseif ($request->query->get('officialDistributions')) {
                $idProject = $request->query->get('officialDistributions');
                if ($type === 'pdf') {
                    return $this->get('distribution.assistance_service')->exportToPdf($idProject);
                }
                $filename = $this->get('distribution.assistance_service')->exportToOfficialCsv($idProject, $type);
            } elseif ($request->query->get('beneficiaries')) {
                $countryIso3 = $request->request->get("__country");
                $filters = $request->request->get('filters');
                $ids = $request->request->get('ids');
                $filename = $this->get('beneficiary.beneficiary_service')->exportToCsvDeprecated($type, $countryIso3, $filters, $ids);
            } elseif ($request->query->get('users')) {
                $filename = $this->get('user.user_service')->exportToCsv($type);
            } elseif ($request->query->get('countries')) {
                $countryIso3 = $request->request->get("__country");
                $filename = $this->get('beneficiary.country_specific_service')->exportToCsv($type, $countryIso3);
            } elseif ($request->query->get('donors')) {
                $filename = $this->get('project.donor_service')->exportToCsv($type);
            } elseif ($request->query->get('projects')) {
                $country = $request->query->get('projects');
                $filename = $this->get('project.project_service')->exportToCsv($country, $type);
            } elseif ($request->query->get('distributionSample')) {
                $arrayObjectBeneficiary = $request->request->get('sample');
                $filename = $this->get('distribution.assistance_beneficiary_service')->exportToCsv($arrayObjectBeneficiary, $type);
            } elseif ($request->query->get('householdsTemplate')) {
                $countryIso3 = $request->request->get("__country");
                $filename = $this->get('beneficiary.household_export_csv_service')->exportToCsv($type, $countryIso3);
            } elseif ($request->query->get('transactionDistribution') ||
                $request->query->get('smartcardDistribution') ||
                $request->query->get('voucherDistribution') ||
                $request->query->get('generalreliefDistribution') ||
                $request->query->get('beneficiariesInDistribution')) {
                $idDistribution = $request->query->get('transactionDistribution') ??
                    $request->query->get('smartcardDistribution') ??
                    $request->query->get('voucherDistribution') ??
                    $request->query->get('generalreliefDistribution') ??
                    $request->query->get('beneficiariesInDistribution');
                $distribution = $this->get('distribution.assistance_service')->findOneById($idDistribution);
                // todo find organisation by relation to distribution
                $organization = $this->getDoctrine()->getRepository(Organization::class)->findOneBy([]);
                if ($type === 'pdf') {
                    return $this->get('export.pdf')->export($distribution, $organization);
                }
                $filename = $this->get('export.spreadsheet')->export($distribution, $organization, $type);
                // raw export for legacy purpose
                if ($type === 'xlsx' && in_array($distribution->getTargetType(), [AssistanceTargetType::HOUSEHOLD, AssistanceTargetType::INDIVIDUAL])) { // hack to enable raw export, will be forgotten with FE switch
                    if ($request->query->has('transactionDistribution')) {
                        $filename = $this->get('transaction.transaction_service')->exportToCsv($distribution, 'xlsx');
                    }
                    if ($request->query->has('smartcardDistribution')) {
                        // no change
                    }
                    if ($request->query->has('voucherDistribution')) {
                        $filename = $this->get('distribution.assistance_service')->exportVouchersDistributionToCsv($distribution, $type);
                    }
                    if ($request->query->has('generalreliefDistribution')) {
                        $filename = $this->get('distribution.assistance_service')->exportGeneralReliefDistributionToCsv($distribution, 'xlsx');
                    }
                    if ($request->query->has('beneficiariesInDistribution')) {
                        $filename = $this->get('distribution.assistance_service')->exportToCsvBeneficiariesInDistribution($distribution, $type);
                    }
                }
            } elseif ($request->query->get('bookletCodes')) {
                $ids = $request->request->get('ids');
                $countryIso3 = $request->request->get("__country");
                $filters = $request->request->get('filters');
                if ($type === 'pdf') {
                    return $this->get('voucher.voucher_service')->exportToPdf($ids, $countryIso3, $filters);
                }
                if ($type === 'csv') {
                    return $this->get('voucher.voucher_service')->exportToCsv($type, $countryIso3, $ids, $filters);
                }
                $filename = $this->get('voucher.voucher_service')->exportToCsv($type, $countryIso3, $ids, $filters);
            } elseif ($request->query->get('reporting')) {
                $filename = $this->get('reporting.reporting_service')->exportToCsv($request->request, $type);
            } elseif ($request->query->get('products')) {
                $countryIso3 = $request->request->get("__country");
                $filename = $this->get('voucher.product_service')->exportToCsv($type, $countryIso3);
            } elseif ($request->query->get('vendors')) {
                $countryIso3 = $request->request->get("__country");
                $filename = $this->get('voucher.vendor_service')->exportToCsv($type, $countryIso3);
            } else {
                return new JsonResponse('No export selected', Response::HTTP_BAD_REQUEST);
            }

            // Create binary file to send
            $response = new BinaryFileResponse(getcwd() . '/' . $filename);

            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
            $mimeTypeGuesser = new FileinfoMimeTypeGuesser();
            if ($mimeTypeGuesser->isSupported()) {
                $response->headers->set('Content-Type', $mimeTypeGuesser->guess(getcwd() . '/' . $filename));
            } else {
                $response->headers->set('Content-Type', 'text/plain');
            }
            $response->deleteFileAfterSend(true);

            return $response;
        } catch (\Exception $exception) {
            return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Rest\Get("/web-app/v1/products/{id}")
     * @Cache(lastModified="product.getLastModifiedAt()", public=true)
     *
     * @param Product $product
     *
     * @return JsonResponse
     */
    public function item(Product $product): JsonResponse
    {
        if (true === $product->getArchived()) {
            throw $this->createNotFoundException();
        }

        return $this->json($product);
    }

    /**
     * @Rest\Get("/web-app/v1/products")
     * @Rest\Get("/vendor-app/v2/products")
     *
     * @param Request                $request
     * @param ProductFilterInputType $filter
     * @param Pagination             $pagination
     * @param ProductOrderInputType  $orderBy
     *
     * @return JsonResponse
     */
    public function list(Request $request, ProductFilterInputType $filter, Pagination $pagination, ProductOrderInputType $orderBy): JsonResponse
    {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        /** @var ProductRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Product::class);
        $data = $repository->findByCountry($request->headers->get('country'), $filter, $orderBy, $pagination);

        return $this->json($data);
    }

    /**
     * @Rest\Post("/web-app/v1/products")
     *
     * @param ProductCreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(ProductCreateInputType $inputType): JsonResponse
    {
        $object = $this->get('voucher.product_service')->create($inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Put("/web-app/v1/products/{id}")
     *
     * @param Product                $product
     * @param ProductUpdateInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(Product $product, ProductUpdateInputType $inputType): JsonResponse
    {
        $object = $this->get('voucher.product_service')->update($product, $inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Post("/web-app/v1/products/images")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function uploadImage(Request $request): JsonResponse
    {
        if (!($file = $request->files->get('file'))) {
            throw new BadRequestHttpException('File missing.');
        }

        if (!in_array($file->getMimeType(), ['image/gif', 'image/jpeg', 'image/png'])) {
            throw new BadRequestHttpException('Invalid file type.');
        }

        $url = $this->uploadService->upload($file, 'products');

        return $this->json(['url' => $url]);
    }

    /**
     * @Rest\Delete("/web-app/v1/products/{id}")
     *
     * @param Product $product
     *
     * @return JsonResponse
     */
    public function delete(Product $product): JsonResponse
    {
        $this->get('voucher.product_service')->archive($product);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
