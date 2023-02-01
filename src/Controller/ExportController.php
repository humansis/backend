<?php

namespace Controller;

use Doctrine\Persistence\ManagerRegistry;
use Entity\Organization;
use Entity\Assistance;
use Enum\AssistanceTargetType;
use Exception;
use Export\AssistancePdfExport;
use Export\AssistanceSpreadsheetExport;
use Repository\AssistanceRepository;
use Utils\AssistanceBeneficiaryService;
use Utils\AssistanceService;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Utils\BeneficiaryService;
use Utils\CountrySpecificService;
use Utils\DonorService;
use Utils\HouseholdExportCSVService;
use Utils\ProductService;
use Utils\ProjectService;
use Utils\TransactionService;
use Utils\UserService;
use Utils\VendorService;
use Utils\VoucherService;
use Symfony\Component\Mime\MimeTypes;

/**
 * Class ExportController
 *
 * @package Controller
 *
 */
class ExportController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    /** @var int maximum count of exported entities */
    final public const EXPORT_LIMIT = 10000;
    final public const EXPORT_LIMIT_CSV = 20000;

    public function __construct(private readonly AssistanceRepository $assistanceRepository, private readonly AssistanceService $assistanceService, private readonly BeneficiaryService $beneficiaryService, private readonly UserService $userService, private readonly CountrySpecificService $countrySpecificService, private readonly DonorService $donorService, private readonly ProjectService $projectService, private readonly AssistanceBeneficiaryService $assistanceBeneficiaryService, private readonly HouseholdExportCSVService $householdExportCSVService, private readonly AssistancePdfExport $assistancePdfExport, private readonly AssistanceSpreadsheetExport $assistanceSpreadsheetExport, private readonly TransactionService $transactionService, private readonly VoucherService $voucherService, private readonly ProductService $productService, private readonly VendorService $vendorService, private readonly ManagerRegistry $managerRegistry)
    {
    }

    /**
     * @Rest\Post("/export", name="export_data")
     *
     * @return Response
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
            } elseif ($request->query->get('countries')) {
                $countryIso3 = $request->request->get("__country");
                $filename = $this->countrySpecificService->exportToCsv($type, $countryIso3);
            } elseif ($request->query->get('donors')) {
                $filename = $this->donorService->exportToCsv($type);
            } elseif ($request->query->get('distributionSample')) {
                $arrayObjectBeneficiary = $request->request->get('sample');
                $filename = $this->assistanceBeneficiaryService->exportToCsv(
                    $arrayObjectBeneficiary,
                    $type
                );
            } elseif ($request->query->get('householdsTemplate')) {
                $countryIso3 = $request->request->get("__country");
                $filename = $this->householdExportCSVService->exportToCsv($type, $countryIso3);
            } elseif (
                $request->query->get('transactionDistribution') ||
                $request->query->get('smartcardDistribution') ||
                $request->query->get('voucherDistribution') ||
                $request->query->get('generalreliefDistribution') ||
                $request->query->get('beneficiariesInDistribution')
            ) {
                $idDistribution = $request->query->get('transactionDistribution') ??
                    $request->query->get('smartcardDistribution') ??
                    $request->query->get('voucherDistribution') ??
                    $request->query->get('generalreliefDistribution') ??
                    $request->query->get('beneficiariesInDistribution');
                $distribution = $this->assistanceRepository->find($idDistribution);
                // todo find organisation by relation to distribution
                $organization = $this->managerRegistry->getRepository(Organization::class)->findOneBy([]);
                if ($type === 'pdf') {
                    return $this->assistancePdfExport->export($distribution, $organization);
                }
                $filename = $this->assistanceSpreadsheetExport->export($distribution, $organization, $type);
                // raw export for legacy purpose
                if (
                    $type === 'xlsx' && in_array(
                        $distribution->getTargetType(),
                        [AssistanceTargetType::HOUSEHOLD, AssistanceTargetType::INDIVIDUAL]
                    )
                ) { // hack to enable raw export, will be forgotten with FE switch
                    if ($request->query->has('transactionDistribution')) {
                        $filename = $this->transactionService->exportToCsv($distribution, 'xlsx');
                    }
                    if ($request->query->has('smartcardDistribution')) {
                        // no change
                    }
                    if ($request->query->has('voucherDistribution')) {
                        $filename = $this->assistanceService->exportVouchersDistributionToCsv($distribution, $type);
                    }
                    if ($request->query->has('generalreliefDistribution')) {
                        $filename = $this->assistanceService->exportGeneralReliefDistributionToCsv(
                            $distribution,
                            'xlsx'
                        );
                    }
                    if ($request->query->has('beneficiariesInDistribution')) {
                        $filename = $this->assistanceService->exportToCsvBeneficiariesInDistribution(
                            $distribution,
                            $type
                        );
                    }
                }
            } elseif ($request->query->get('reporting')) {
                // The service does not exist
                // $filename = $this->get('reporting.reporting_service')->exportToCsv($request->request, $type);
                $filename = "";
            } elseif ($request->query->get('products')) {
                $countryIso3 = $request->request->get("__country");
                $filename = $this->productService->exportToCsv($type, $countryIso3);
            } else {
                return new JsonResponse('No export selected', Response::HTTP_BAD_REQUEST);
            }

            // Create binary file to send
            $response = new BinaryFileResponse(getcwd() . '/' . $filename);

            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
            $mimeTypeGuesser = new MimeTypes();
            if ($mimeTypeGuesser->isGuesserSupported()) {
                $response->headers->set('Content-Type', $mimeTypeGuesser->guessMimeType(getcwd() . '/' . $filename));
            } else {
                $response->headers->set('Content-Type', 'text/plain');
            }
            $response->deleteFileAfterSend(true);

            return $response;
        } catch (Exception $exception) {
            return new JsonResponse(
                $exception->getMessage(),
                $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * @Rest\Get("/export/distribution", name="export_distribution")
     */
    public function exportDistributionToPdf(Request $request): Response
    {
        if (!$request->query->has('id')) {
            throw $this->createNotFoundException("Missing distribution ID.");
        }

        $distribution = $this->managerRegistry->getRepository(Assistance::class)->find($request->query->get('id'));
        if (null == $distribution) {
            throw $this->createNotFoundException("Invalid distribution requested.");
        }

        if (!$request->query->has('type') || 'pdf' !== $request->query->get('type')) {
            throw $this->createNotFoundException("Invalid file type requested.");
        }

        $organization = $this->managerRegistry->getRepository(Organization::class)->findOneBy([]);

        return $this->assistancePdfExport->export($distribution, $organization);
    }
}
