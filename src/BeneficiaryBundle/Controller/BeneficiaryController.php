<?php

namespace BeneficiaryBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class BeneficiaryController extends Controller
{

    /**
     * @Rest\Get("/vulnerability_criteria", name="get_all_vulnerability_criteria")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT')")
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
}