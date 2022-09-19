<?php

namespace Controller;

use Entity\Organization;
use Entity\Assistance;
use Enum\AssistanceTargetType;
use Repository\AssistanceRepository;
use Utils\AssistanceService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Utils\HouseholdExportCSVService;

/**
 * Class ExportController
 * @package Controller
 *
 * @SWG\Parameter(
 *      name="country",
 *      in="header",
 *      type="string",
 *      required=true
 * )
 */
class ExportController extends Controller
{
    /** @var int maximum count of exported entities */
    const EXPORT_LIMIT = 10000;
    const EXPORT_LIMIT_CSV = 20000;

    /**
     * @var AssistanceRepository
     */
    private $assistanceRepository;

    /**
     * @var AssistanceService
     */
    private $assistanceService;

    /**
     * @var HouseholdExportCSVService
     */
    private $householdExportCSVService;


    public function __construct(AssistanceRepository $assistanceRepository, AssistanceService $assistanceService, HouseholdExportCSVService $householdExportCSVService)
    {
        $this->assistanceRepository = $assistanceRepository;
        $this->assistanceService = $assistanceService;
        $this->householdExportCSVService = $householdExportCSVService;
    }

    /**
     * @Rest\Post("/export", name="export_data")
     *
     * @SWG\Tag(name="Export")
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
     * @param Request $request
     *
     * @return Response
     *
     * @deprecated export action must be refactorized. Please make own export action instead.
     */
    public function exportAction(Request $request)
    {
        try {
            set_time_limit(600);
            // Format of the file (csv, xlsx, ods, pdf)
            $type = $request->query->get('type');
            // Generate corresponding file depending on request
            if ($request->query->get('distributions')) {
                $idProject = $request->query->get('distributions');
                if ($type === 'pdf') {
                    return $this->assistanceService->exportToPdf($idProject);
                }
                $filename = $this->assistanceService->exportToCsv($idProject, $type);
            } elseif ($request->query->get('officialDistributions')) {
                $idProject = $request->query->get('officialDistributions');
                if ($type === 'pdf') {
                    return $this->assistanceService->exportToPdf($idProject);
                }
                $filename = $this->assistanceService->exportToOfficialCsv($idProject, $type);
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
                $filename = $this->householdExportCSVService->exportToCsv($type, $countryIso3);
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
                $distribution = $this->assistanceRepository->find($idDistribution);
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
                        $filename = $this->assistanceService->exportVouchersDistributionToCsv($distribution, $type);
                    }
                    if ($request->query->has('generalreliefDistribution')) {
                        $filename = $this->assistanceService->exportGeneralReliefDistributionToCsv($distribution, 'xlsx');
                    }
                    if ($request->query->has('beneficiariesInDistribution')) {
                        $filename = $this->assistanceService->exportToCsvBeneficiariesInDistribution($distribution, $type);
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
     * @Rest\Get("/export/distribution", name="export_distribution")
     *
     * @SWG\Tag(name="Export")
     *
     * @SWG\Parameter(name="id",
     *     type="string",
     *     in="query",
     *     required=true,
     *     description="ID of distribution to export"
     * )
     *
     * @SWG\Parameter(name="type",
     *     type="string",
     *     in="query",
     *     required=true,
     *     description="requested file type (pdf only is support now)"
     * )
     *
     * @SWG\Parameter(name="locale",
     *     type="string",
     *     in="query",
     *     default="en"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="streamed file"
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="invalid query parameters"
     * )
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws
     */
    public function exportDistributionToPdf(Request $request): Response
    {
        if (!$request->query->has('id')) {
            throw $this->createNotFoundException("Missing distribution ID.");
        }

        $distribution = $this->getDoctrine()->getRepository(Assistance::class)->find($request->query->get('id'));
        if (null == $distribution) {
            throw $this->createNotFoundException("Invalid distribution requested.");
        }

        if (!$request->query->has('type') || 'pdf' !== $request->query->get('type')) {
            throw $this->createNotFoundException("Invalid file type requested.");
        }

        $organization = $this->getDoctrine()->getRepository(Organization::class)->findOneBy([]);

        return $this->get('export.pdf')->export($distribution, $organization);
    }
}
