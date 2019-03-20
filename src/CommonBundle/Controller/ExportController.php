<?php

namespace CommonBundle\Controller;

use DistributionBundle\Entity\DistributionData;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;

/**
 * Class ExportController
 * @package CommonBundle\Controller
 */
class ExportController extends Controller
{
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
     */
    public function exportAction(Request $request)
    {
        try {
            // Format of the file (csv, xls, ods)
            $type = $request->query->get('type');
            // Generate corresponding file depending on request
            if ($request->query->get('distributions')) {
                $idProject = $request->query->get('distributions');
                $filename = $this->get('distribution.distribution_service')->exportToCsv($idProject, $type);
            } 
            elseif ($request->query->get('beneficiaries')) {
                $filename = $this->get('beneficiary.beneficiary_service')->exportToCsv($type);
            } 
            elseif ($request->query->get('beneficiariesInDistribution')) {
                $idDistribution = $request->query->get('beneficiariesInDistribution');
                $distribution = $this->get('distribution.distribution_service')->findOneById($idDistribution);
                $filename = $this->get('beneficiary.beneficiary_service')->exportToCsvBeneficiariesInDistribution($distribution, $type);
            } 
            elseif ($request->query->get('users')) {
                $filename = $this->get('user.user_service')->exportToCsv($type);
            } 
            elseif ($request->query->get('countries')) {
                $countryIso3 = $request->request->get("__country");
                $filename = $this->get('beneficiary.country_specific_service')->exportToCsv($type, $countryIso3);
            } 
            elseif ($request->query->get('donors')) {
                $filename = $this->get('project.donor_service')->exportToCsv($type);
            } 
            elseif ($request->query->get('projects')) {
                $country = $request->query->get('projects');
                $filename = $this->get('project.project_service')->exportToCsv($country, $type);
            } 
            elseif ($request->query->get('distributionSample')) {
                $arrayObjectBeneficiary = $request->request->get('sample');
                $filename = $this->get('distribution.distribution_beneficiary_service')->exportToCsv($arrayObjectBeneficiary, $type);
            }
            elseif ($request->query->get('householdsTemplate')) {
                $countryIso3 = $request->request->get("__country");
                $filename = $this->get('beneficiary.household_export_csv_service')->exportToCsv($type, $countryIso3);
            }
            elseif ($request->query->get('transactionDistribution')) {
                $idDistribution = $request->query->get('transactionDistribution');
                $distribution = $this->get('distribution.distribution_service')->findOneById($idDistribution);
                $filename = $this->get('transaction.transaction_service')->exportToCsv($distribution, $type);
            }
            elseif ($request->query->get('booklets')) {
                $filename = $this->get('voucher.booklet_service')->exportToCsv($type);
            }
            elseif ($request->query->get('reporting')) {
                $indicatorsId  = $request->request->get('indicators');
                $frequency     = $request->request->get('frequency');
                $projects      = $request->request->get('projects');
                $distributions = $request->request->get('distributions');
                $country       = $request->request->get('__country');

                $filename = $this->get('reporting.reporting_service')->exportToCsv($indicatorsId, $frequency, $projects, $distributions, $country, $type);
            }
            elseif ($request->query->get('generalreliefDistribution')) {
                $idDistribution = $request->query->get('generalreliefDistribution');
                $distribution = $this->get('distribution.distribution_service')->findOneById($idDistribution);
                $filename = $this->get('distribution.distribution_service')->exportGeneralReliefDistributionToCsv($distribution, $type);
            }
            elseif ($request->query->get('voucherDistribution')) {
                $idDistribution = $request->query->get('voucherDistribution');
                $distribution = $this->get('distribution.distribution_service')->findOneById($idDistribution);
                $filename = $this->$this->get('voucher.booklet_service')->exportVouchersDistributionToCsv($distribution, $type);
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
}
