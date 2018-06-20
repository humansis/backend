<?php

namespace BeneficiaryBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Utils\BeneficiaryService;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations as Rest;

class BeneficiaryController extends Controller
{

    /**
     * @Rest\Put("/beneficiaries", name="add_beneficiary")
     *
     * @param Request $request
     * @return Response
     */
    public function addAction(Request $request)
    {
        $beneficiaryArray = $request->request->all();
        /** @var BeneficiaryService $beneficiaryService */
        $beneficiaryService = $this->get('beneficiary.beneficiary_service');
        $beneficiary = $beneficiaryService->create($beneficiaryArray);

        $json = $this->get('jms_serializer')
            ->serialize($beneficiary, 'json', SerializationContext::create()->setSerializeNull(true));

        return new Response($json);
    }

    /**
     * @Rest\Post("/beneficiaries/{id}", name="update_beneficiary")
     *
     * @param Request $request
     * @param Beneficiary $beneficiary
     */
    public function updateAction(Request $request, Beneficiary $beneficiary)
    {

    }
}