<?php

namespace CommonBundle\Controller;

use DistributionBundle\Entity\DistributionData;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{

    /**
     * @Rest\Get("/export", name="export_data")
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
     * @param Request $request
     * @return Response
     */
    public function exportToCSVAction(Request $request)  {

        if($request->query->get('distributions')){
            $idProject = $request->query->get('distributions');

            try{

                $fileCSV = $this->get('distribution.distribution_service')->exportToCsv($idProject);
                
                return new Response(json_encode($fileCSV));
                
            } catch(\Exception $exception) {
                return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
            }
        }
        
        elseif($request->query->get('beneficiaries')){

            try{

                $fileCSV = $this->get('beneficiary.beneficiary_service')->exportToCsv();

                return new Response(json_encode($fileCSV));
                
            } catch(\Exception $exception) {
                return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
            }
        }

        elseif($request->query->get('beneficiariesInDistribution')){
            $idDistribution = $request->query->get('beneficiariesInDistribution');

            try{
                $distribution = $this->get('distribution.distribution_service')->findOneById($idDistribution);

                $fileCSV = $this->get('beneficiary.beneficiary_service')->exportToCsvBeneficiariesInDistribution($distribution);
                
                return new Response(json_encode($fileCSV));
                
            } catch(\Exception $exception) {
                return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
            }
        }

        elseif($request->query->get('users')){

            try{

                $fileCSV = $this->get('user.user_service')->exportToCsv();

                return new Response(json_encode($fileCSV));

            } catch(\Exception $exception) {
                return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
            }
        }

        elseif($request->query->get('countries')){

            try{

                $fileCSV = $this->get('beneficiary.country_specific_service')->exportToCsv();

                return new Response(json_encode($fileCSV));

            } catch(\Exception $exception) {
                return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
            }
        }

        elseif($request->query->get('donors')){

            try{

                $fileCSV = $this->get('project.donor_service')->exportToCsv();

                return new Response(json_encode($fileCSV));

            } catch(\Exception $exception) {
                return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
            }
        }

        elseif($request->query->get('projects')){

            $country = $request->query->get('projects');
            //$country = $request->query->get('__country');
            try{

                $fileCSV = $this->get('project.project_service')->exportToCsv($country);

                return new Response(json_encode($fileCSV));

            } catch(\Exception $exception) {
                return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
            }
        }
    }
     /**
     * @Rest\Get("/export/{id}", name="export_data_Beneficiaries_Distribution")
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
     * @param DistributionData $DistributionData
     * @return Response
     */
    // public function exportToCSVBeneficiariesDistribution(DistributionData $distributionData)  {

    //     /** @var DistributionBeneficiaryService $distributionBeneficiaryService */
    //     dump($distributionData);
    //     try{

    //         $fileCSV = $this->get('beneficiary.beneficiary_service')->exportToCsvBeneficiariesDistribution("beneficiariesInDistribution", $distributionData);
            
    //         return new Response(json_encode($fileCSV));
            
    //     } catch(\Exception $exception) {
    //         return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
    //     }
    // }
}
