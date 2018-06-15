<?php


namespace BeneficiaryBundle\Controller;


use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;

class HouseholdController extends Controller
{

    /**
     * @Rest\Put("/beneficiaries", name="add_beneficiary")
     *
     * @param Request $request
     * @return Response
     */
    public function addAction(Request $request)
    {
        $householdArray = $request->request->all();
        $household = $this->get('beneficiary.household_service')->create($householdArray);

        $json = $this->get('jms_serializer')
            ->serialize($household, 'json', SerializationContext::create()->setSerializeNull(true));

        return new Response($json);
    }
}