<?php


namespace BeneficiaryBundle\Controller;


use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;

class CountryController extends Controller
{
    /**
     * @Rest\Get("/country_specifics", name="all_country_specifics")
     *
     * @return Response
     */
    public function getCountrySpecifics()
    {
        $countrySpecifics = $this->get('beneficiary.country_specific_service')->getAll();

        $json = $this->get('jms_serializer')
            ->serialize(
                $countrySpecifics,
                'json',
                SerializationContext::create()->setGroups(['FullCountrySpecific'])->setSerializeNull(true)
            );

        return new Response($json);
    }
}