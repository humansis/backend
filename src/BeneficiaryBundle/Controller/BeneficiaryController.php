<?php

namespace BeneficiaryBundle\Controller;

use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Utils\BeneficiaryService;
use JMS\Serializer\SerializationContext;
use ProjectBundle\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;

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
     * @Rest\Post("/beneficiaries/import/api/{id}", name="get_all_benificiaries_via_api")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     * @SWG\Tag(name="Beneficiary")
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Request $request
     * @param Project $project
     * @return Response
     */
    public function importBeneficiariesFromAPIAction(Request $request, Project $project)
    {
        $body = $request->request->all();
        $countryIso3 = $body['__country'];
        $provider = strtolower($body['provider']);
        $countryCode = $body['countryCode'];

        try {
            $response = $this->get('beneficiary.api_import_service')->import($countryIso3, $provider, $countryCode, $project);

            $json = $this->get('jms_serializer')
                ->serialize($response, 'json');

            return new Response($json);
        } catch (\Exception $exception) {
            return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Rest\Get("/beneficiaries/import/api/name", name="get_all_api_available_for_country")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     * @SWG\Tag(name="Beneficiary")
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function getAllAPIAction(Request $request)
    {
        $body = $request->request->all();
        $countryIso3 = $body['__country'];

        $APINames = $this->get('beneficiary.api_import_service')->getApiNames($countryIso3);
        $json = $this->get('jms_serializer')
            ->serialize($APINames, 'json');

        return new Response($json);
    }

    /**
     * @Rest\Post("/beneficiaries/import/api/params/{id}", name="get_all_parameters_for_selected_api")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     * @SWG\Tag(name="Beneficiary")
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Request $request
     * @param Project $project
     * @return Response
     */
    public function getAllParamsAction(Request $request, Project $project)
    {
        $body = $request->request->all();
        $countryIso3 = $body['__country'];
        $api = $body['api'];

        $APIParams = $this->get('beneficiary.api_import_service')->getParams($countryIso3, $api);
        $json = $this->get('jms_serializer')
            ->serialize($APIParams, 'json');

        return new Response($json);
    }
}
