<?php

namespace BeneficiaryBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Utils\BeneficiaryService;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

class BeneficiaryController extends Controller
{
    /**
     * @Rest\Get("/vulnerability_criteria", name="get_all_vulnerability_criteria")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     * @SWG\Tag(name="Beneficiary")
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     * @return Response
     */
    public function getAllVulnerabilityCriteria()
    {
        $vulnerabilityCriteria = $this->get('beneficiary.beneficiary_service')->getAllVulnerabilityCriteria();
        $json = $this->get('jms_serializer')
            ->serialize($vulnerabilityCriteria, 'json');
        return new Response($json);
    }

    /**
     * @Rest\Put("/households/{id}/beneficiary", name="add_beneficiary_in_household")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     * @SWG\Tag(name="Beneficiary")
     *  @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     * @param Request $request
     * @param Household $household
     * @return Response
     * @throws \Exception
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function addInHousehold(Request $request, Household $household)
    {
        $beneficiaryArray = $request->request->all();
        if (array_key_exists('__country', $beneficiaryArray))
            unset($beneficiaryArray['__country']);
        /** @var BeneficiaryService $beneficiaryService */
        $beneficiaryService = $this->get('beneficiary.beneficiary_service');

        $beneficiary = $beneficiaryService->updateOrCreate($household, $beneficiaryArray, true);

        $json = $this->get('jms_serializer')
            ->serialize($beneficiary, 'json', SerializationContext::create()->setSerializeNull(true));

        return new Response($json);
    }
<<<<<<< HEAD
=======

    /**
     * @Rest\Get("/beneficiary/export", name="beneficiary_export")
     * TODO: ADd security on project
     * @ Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ', project)")
     *
     * @SWG\Tag(name="Beneficiary")
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
     * @return Response
     */
    // public function exportToCSVAction()
    // {
    //     try
    //     {
    //         $fileCSV = $this->get('beneficiary.beneficiary_service')->exportToCsv();

    //         return new Response(json_encode($fileCSV));
    //     }
    //     catch(\Exception $exception)
    //     {
    //         return new Response($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
    //     }
    // }
>>>>>>> dev
}
