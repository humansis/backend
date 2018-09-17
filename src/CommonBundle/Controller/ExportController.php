<?php

namespace CommonBundle\Controller;

use DistributionBundle\Entity\DistributionData;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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
    public function exportToCSVAction(Request $request)
    {
        if ($request->query->get('distributions')) {
            $idProject = $request->query->get('distributions');
            $type = $request->request->get('type');

            try {
                $fileCSV = $this->get('distribution.distribution_service')->exportToCsv($idProject, $type);

                return new Response(json_encode($fileCSV));
            } catch (\Exception $exception) {
                return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
            }
        } elseif ($request->query->get('beneficiaries')) {
            $type = $request->request->get('type');

            try {
                $fileCSV = $this->get('beneficiary.beneficiary_service')->exportToCsv($type);

                return new Response(json_encode($fileCSV));
            } catch (\Exception $exception) {
                return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
            }
        } elseif ($request->query->get('beneficiariesInDistribution')) {
            $idDistribution = $request->query->get('beneficiariesInDistribution');
            $type = $request->request->get('type');

            try {
                $distribution = $this->get('distribution.distribution_service')->findOneById($idDistribution);

                $fileCSV = $this->get('beneficiary.beneficiary_service')->exportToCsvBeneficiariesInDistribution($distribution, $type);

                return new Response(json_encode($fileCSV));
            } catch (\Exception $exception) {
                return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
            }
        } elseif ($request->query->get('users')) {
            $type = $request->request->get('type');

            try {
                $fileCSV = $this->get('user.user_service')->exportToCsv($type);

                return new Response(json_encode($fileCSV));
            } catch (\Exception $exception) {
                return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
            }
        } elseif ($request->query->get('countries')) {
            $type = $request->request->get('type');

            try {
                $fileCSV = $this->get('beneficiary.country_specific_service')->exportToCsv($type);

                return new Response(json_encode($fileCSV));
            } catch (\Exception $exception) {
                return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
            }
        } elseif ($request->query->get('donors')) {
            $type = $request->request->get('type');

            try {
                $fileCSV = $this->get('project.donor_service')->exportToCsv($type);

                return new Response(json_encode($fileCSV));
            } catch (\Exception $exception) {
                return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
            }
        } elseif ($request->query->get('project')) {
            $country = $request->query->get('project');
            $type = $request->request->get('type');

            //$country = $request->query->get('__country');
            try {
                $fileCSV = $this->get('project.project_service')->exportToCsv($country, $type);

                return new Response(json_encode($fileCSV));
            } catch (\Exception $exception) {
                return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
            }
        } elseif ($request->query->get('distributionSamble')) {
            $arrayObjectBeneficiary = $request->request->all();
            $type = $request->request->get('type');

            try {
                $fileCSV = $this->get('distribution.distribution_beneficiary_service')->exportToCsv($arrayObjectBeneficiary, $type);

                return new Response(json_encode($fileCSV));
            } catch (\Exception $exception) {
                return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
            }
        }
    }
}
