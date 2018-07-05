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
     * @Rest\Get("/vulnerability_criteria", name="get_all_vulnerability_criteria")
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