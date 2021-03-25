<?php

namespace CommonBundle\Controller;

use BeneficiaryBundle\Utils\BeneficiaryService;
use BeneficiaryBundle\Utils\CountrySpecificService;
use BeneficiaryBundle\Utils\HouseholdExportCSVService;
use BeneficiaryBundle\Utils\HouseholdService;
use CommonBundle\Entity\Organization;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Export\SmartcardExport;
use DistributionBundle\Utils\AssistanceBeneficiaryService;
use DistributionBundle\Utils\AssistanceService;
use ProjectBundle\Utils\DonorService;
use ProjectBundle\Utils\ProjectService;
use Punic\Misc;
use ReportingBundle\Utils\ReportingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use TransactionBundle\Utils\TransactionService;
use UserBundle\Utils\UserService;
use VoucherBundle\Utils\BookletService;
use VoucherBundle\Utils\ProductService;
use VoucherBundle\Utils\VendorService;
use VoucherBundle\Utils\VoucherService;

/**
 * Class ExportController
 * @package CommonBundle\Controller
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

    /** @var VendorService */
    private $vendorService;
    /** @var VoucherService */
    private $voucherService;
    /** @var AssistanceService */
    private $assistanceService;
    /** @var BeneficiaryService */
    private $beneficiaryService;
    /** @var TransactionService */
    private $transactionService;
    /** @var UserService */
    private $userService;
    /** @var SmartcardExport */
    private $smartcardExport;
    /** @var DonorService */
    private $donorService;
    /** @var BookletService */
    private $bookletService;
    /** @var ProductService */
    private $productService;
    /** @var ProjectService */
    private $projectService;
    /** @var CountrySpecificService */
    private $countrySpecificService;
    /** @var AssistanceBeneficiaryService */
    private $assistanceBeneficiaryService;
    /** @var HouseholdExportCSVService */
    private $householdExportCSVService;
    /** @var ReportingService */
    private $reportingService;
    /** @var HouseholdService */
    private $householdService;

    /**
     * ExportController constructor.
     *
     * @param VoucherService               $voucherService
     * @param AssistanceService            $assistanceService
     * @param BeneficiaryService           $beneficiaryService
     * @param TransactionService           $transactionService
     * @param UserService                  $userService
     * @param SmartcardExport              $smartcardExport
     * @param DonorService                 $donorService
     * @param BookletService               $bookletService
     * @param ProductService               $productService
     * @param ProjectService               $projectService
     * @param CountrySpecificService       $countrySpecificService
     * @param AssistanceBeneficiaryService $assistanceBeneficiaryService
     * @param HouseholdExportCSVService    $householdExportCSVService
     * @param ReportingService             $reportingService
     * @param VendorService                $vendorService
     * @param HouseholdService             $householdService
     */
    public function __construct(
        VoucherService $voucherService,
        AssistanceService $assistanceService,
        BeneficiaryService $beneficiaryService,
        TransactionService $transactionService,
        UserService $userService,
        SmartcardExport $smartcardExport,
        DonorService $donorService,
        BookletService $bookletService,
        ProductService $productService,
        ProjectService $projectService,
        CountrySpecificService $countrySpecificService,
        AssistanceBeneficiaryService $assistanceBeneficiaryService,
        HouseholdExportCSVService $householdExportCSVService,
        ReportingService $reportingService,
        VendorService $vendorService,
        HouseholdService $householdService
    ) {
        $this->voucherService = $voucherService;
        $this->assistanceService = $assistanceService;
        $this->beneficiaryService = $beneficiaryService;
        $this->transactionService = $transactionService;
        $this->userService = $userService;
        $this->smartcardExport = $smartcardExport;
        $this->donorService = $donorService;
        $this->bookletService = $bookletService;
        $this->productService = $productService;
        $this->projectService = $projectService;
        $this->countrySpecificService = $countrySpecificService;
        $this->assistanceBeneficiaryService = $assistanceBeneficiaryService;
        $this->householdExportCSVService = $householdExportCSVService;
        $this->reportingService = $reportingService;
        $this->vendorService = $vendorService;
        $this->householdService = $householdService;
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
     * @param Request        $request
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
                $filename = $this->beneficiaryService->exportToCsv($type, $countryIso3, $filters, $ids);
            } elseif ($request->query->get('users')) {
                $filename = $this->userService->exportToCsv($type);
            } elseif ($request->query->get('countries')) {
                $countryIso3 = $request->request->get("__country");
                $filename = $this->countrySpecificService->exportToCsv($type, $countryIso3);
            } elseif ($request->query->get('donors')) {
                $filename = $this->donorService->exportToCsv($type);
            } elseif ($request->query->get('projects')) {
                $country = $request->query->get('projects');
                $filename = $this->projectService->exportToCsv($country, $type);
            } elseif ($request->query->get('distributionSample')) {
                $arrayObjectBeneficiary = $request->request->get('sample');
                $filename = $this->assistanceBeneficiaryService->exportToCsv($arrayObjectBeneficiary, $type);
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
                $distribution = $this->assistanceService->findOneById($idDistribution);
                // todo find organisation by relation to distribution
                $organization = $this->getDoctrine()->getRepository(Organization::class)->findOneBy([]);
                if ($type === 'pdf') {
                    return $this->get('export.pdf')->export($distribution, $organization);
                }
                $filename = $this->get('export.spreadsheet')->export($distribution, $organization, $type);
            } elseif ($request->query->get('bookletCodes')) {
                $ids = $request->request->get('ids');
                $countryIso3 = $request->request->get("__country");
                $filters = $request->request->get('filters');
                if ($type === 'pdf') {
                    return $this->voucherService->exportToPdf($ids, $countryIso3, $filters);
                }
                if ($type === 'csv') {
                    return $this->voucherService->exportToCsv($type, $countryIso3, $ids, $filters);
                }
                $filename = $this->voucherService->exportToCsv($type, $countryIso3, $ids, $filters);
            } elseif ($request->query->get('reporting')) {
                $filename = $this->reportingService->exportToCsv($request->request, $type);
            } elseif ($request->query->get('products')) {
                $countryIso3 = $request->request->get("__country");
                $filename = $this->productService->exportToCsv($type, $countryIso3);
            } elseif ($request->query->get('vendors')) {
                $countryIso3 = $request->request->get("__country");
                $filename = $this->vendorService->exportToCsv($type, $countryIso3);
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

        return $this->get('transaction.export.pdf')->export($distribution, $organization);
    }
}
