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

class BeneficiaryController extends Controller
{





    /**
     * @Rest\Get("/vulnerability_criteria", name="get_all_vulnerability_criteria")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     *
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
     *
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

    /**
     * @Rest\GET("/beneficiary/export", name="export_beneficiary")
     * @return Response
     */

    public function exportToCSVAction() {



        $this->get('beneficiary.beneficiary_service')->exportToCsv();
        return new Response('true');

    }

}