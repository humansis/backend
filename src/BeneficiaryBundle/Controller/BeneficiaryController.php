<?php

namespace BeneficiaryBundle\Controller;

use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Utils\BeneficiaryService;
use JMS\Serializer\SerializationContext;
use ProjectBundle\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;

class BeneficiaryController extends Controller
{
    /**
     * @Rest\Get("/vulnerability_criteria", name="get_all_vulnerability_criteria")
     * @SWG\Tag(name="Beneficiary")
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
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
     * Edit a beneficiary {id} with data in the body
     *
     * @Rest\Post("/beneficiaries/{id}", name="update_beneficiary")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     * @SWG\Tag(name="Beneficiaries")
     *
     * @SWG\Parameter(
     *     name="beneficiary",
     *     in="body",
     *     type="string",
     *     required=true,
     *     description="fields of the beneficiary which must be updated",
     *     @Model(type=Beneficiary::class)
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="SUCCESS",
     *     @Model(type=Beneficiary::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @param Beneficiary $beneficiary
     * @return Response
     */
    public function updateAction(Request $request, Beneficiary $beneficiary)
    {
        $beneficiaryData = $request->request->all();

        try {
            $newBeneficiary = $this->get('beneficiary.beneficiary_service')->updateFromDistribution($beneficiary, $beneficiaryData);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('jms_serializer')->serialize($newBeneficiary, 'json', SerializationContext::create()->setGroups(['FullBeneficiary'])->setSerializeNull(true));
        return new Response($json);
    }
}
