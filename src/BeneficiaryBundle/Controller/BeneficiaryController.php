<?php

namespace BeneficiaryBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Utils\BeneficiaryService;
use JMS\Serializer\SerializationContext;
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
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
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
     * @Rest\Put("/households/{id}/beneficiary", name="add_beneficiary_in_household")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     * @SWG\Tag(name="Beneficiary")
     *  @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Request   $request
     * @param Household $household
     *
     * @return Response
     *
     * @throws \Exception
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function addInHousehold(Request $request, Household $household)
    {
        $beneficiaryArray = $request->request->all();
        if (array_key_exists('__country', $beneficiaryArray)) {
            unset($beneficiaryArray['__country']);
        }
        /** @var BeneficiaryService $beneficiaryService */
        $beneficiaryService = $this->get('beneficiary.beneficiary_service');

        $beneficiary = $beneficiaryService->updateOrCreate($household, $beneficiaryArray, true);

        $json = $this->get('jms_serializer')
            ->serialize($beneficiary, 'json', SerializationContext::create()->setSerializeNull(true));

        return new Response($json);
    }

    /**
     * @Rest\Delete("beneficiaries/{id}", name="remove_one_beneficiary_in_distribution")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Return if the beneficiary specified has been remove"
     * )
     *
     * @param Request $request
     * @param Beneficiary $beneficiary
     *
     * @return Response
     */
    public function removeOneBeneficiaryAction(Request $request, Beneficiary $beneficiary)
    {
        if ($request->query->get('distributionId')) {
            $distributionId = $request->query->get('distributionId');

            /** @var BeneficiaryService $beneficiaryService */
            $beneficiaryService = $this->get('beneficiary.beneficiary_service');

            $return = $beneficiaryService->removeBeneficiaryInDistribution($distributionId, $beneficiary);

            return new Response(json_encode($return));
        } else {
            $json = $this->get('jms_serializer')
                ->serialize('An error occured, please check the body', 'json', SerializationContext::create()->setSerializeNull(true)->setGroups(['FullHousehold']));
            return new Response($json);
        }
    }

    /**
     * Edit a beneficiary in a distribution.
     *
     * @Rest\Post("/beneficiaries/{id}", name="update_beneficiary")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Parameter(
     *     name="Beneficiary",
     *     in="body",
     *     required=true,
     *     @Model(type=DistributionData::class)
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="beneficiary updated",
     *     @Model(type=DistributionData::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request          $request
     * @param DistributionData $DistributionData
     *
     * @return Response
     */
    public function updateBeneficiaryAction(Request $request, DistributionData $DistributionData)
    {
        $beneficiaryArray = $request->request->all();
        try {
            $beneficiaryData = $this->get('distribution.distribution_service')
                ->editBeneficiary($DistributionData, $beneficiaryArray);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        $json = $this->get('jms_serializer')
            ->serialize($beneficiaryData, 'json', SerializationContext::create()->setSerializeNull(true));

        return new Response($json, Response::HTTP_OK);
    }
}
