<?php


namespace BeneficiaryBundle\Controller;


use BeneficiaryBundle\Entity\CountrySpecific;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;

class CountryController extends Controller
{
    /**
     * @Rest\Get("/country_specifics", name="all_country_specifics")
     *
     * @return Response
     */
    public function getCountrySpecificsAction(Request $request)
    {
        $countrySpecifics = $this->get('beneficiary.country_specific_service')->getAll($request->get('__country'));

        $json = $this->get('jms_serializer')
            ->serialize(
                $countrySpecifics,
                'json',
                SerializationContext::create()->setGroups(['FullCountrySpecific'])->setSerializeNull(true)
            );

        return new Response($json);
    }

    /**
     * @Rest\Put("/country_specifics")
     *
     * @param Request $request
     * @return Response
     */
    public function createAction(Request $request)
    {
        $countrySpecific = $this->get('beneficiary.country_specific_service')
            ->create($request->request->get('__country'), $request->request->all());

        $json = $this->get('jms_serializer')
            ->serialize(
                $countrySpecific,
                'json',
                SerializationContext::create()->setGroups(['FullCountrySpecific'])->setSerializeNull(true)
            );

        return new Response($json);
    }

    /**
     * @Rest\Post("/country_specifics/{id}")
     *
     * @param Request $request
     * @return Response
     */
    public function updateAction(Request $request, CountrySpecific $countrySpecific)
    {
        $countrySpecific = $this->get('beneficiary.country_specific_service')
            ->update($countrySpecific, $request->request->get('__country'), $request->request->all());

        $json = $this->get('jms_serializer')
            ->serialize(
                $countrySpecific,
                'json',
                SerializationContext::create()->setGroups(['FullCountrySpecific'])->setSerializeNull(true)
            );

        return new Response($json);
    }
}