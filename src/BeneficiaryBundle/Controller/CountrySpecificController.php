<?php

namespace BeneficiaryBundle\Controller;

use BeneficiaryBundle\Entity\CountrySpecific;
use FOS\RestBundle\Controller\Annotations as Rest;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SWG\Parameter(
 *      name="country",
 *      in="header",
 *      type="string",
 *      required=true
 * )
 */
class CountrySpecificController extends Controller
{
    /**
     * @Rest\Get("/country_specifics", name="all_country_specifics")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_READ')")
     * @SWG\Tag(name="Country")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function getCountrySpecificsAction(Request $request)
    {
        $countrySpecifics = $this->get('beneficiary.country_specific_service')->getAll($request->get('__country'));

        $json = $this->serializer
            ->serialize(
                $countrySpecifics,
                'json',
                ['groups' => ['FullCountrySpecific']]
            );

        return new Response($json);
    }

    /**
     * @Rest\Put("/country_specifics")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     * @SWG\Tag(name="Country")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function createAction(Request $request)
    {
        $countrySpecific = $this->get('beneficiary.country_specific_service')
            ->create($request->request->get('__country'), $request->request->all());

        $json = $this->serializer
            ->serialize($countrySpecific,'json', ['groups' => ['FullCountrySpecific']]);

        return new Response($json);
    }

    /**
     * @Rest\Post("/country_specifics/{id}")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Country")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Request $request
     * @param CountrySpecific $countrySpecific
     * @return Response
     */
    public function updateAction(Request $request, CountrySpecific $countrySpecific)
    {
        $countrySpecific = $this->get('beneficiary.country_specific_service')
            ->update($countrySpecific, $request->request->get('__country'), $request->request->all());

        $json = $this->serializer
            ->serialize(
                $countrySpecific,
                'json',
                ['groups' => ['FullCountrySpecific']]
            );

        return new Response($json);
    }

    /**
     * Edit a countrySpecific
     * @Rest\Delete("/country_specifics/{id}", name="delete_country_specific")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Country")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param CountrySpecific $countrySpecific
     * @return Response
     */
    public function deleteAction(CountrySpecific $countrySpecific)
    {
        try {
            $valid = $this->get('beneficiary.country_specific_service')->delete($countrySpecific);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        if ($valid) {
            return new Response("", Response::HTTP_OK);
        }
        if (!$valid) {
            return new Response("", Response::HTTP_BAD_REQUEST);
        }
    }
}
