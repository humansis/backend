<?php


namespace ProjectBundle\Controller;

use EXSyst\Component\Swagger\Response;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

class DonorController extends Controller
{

    /**
     * @Rest\Get("/donors", name="get_all_donor")
     */
    public function getAllAction()
    {
        $donors = $this->get('donor.donor_service')->getAll();

        $donorsJson = $this->get('jms_serializer')
            ->serializer($donors, 'json', SerializationContext::create()->setGroups(['FullDonor'])->setSerializeNull(true));

        return new Response($donorsJson);
    }

    public function createAction(Request $request)
    {
        $donorArray = $request->request->all();

        $donor = $this->get('donor.donor_service')->create($donorArray);

        $donorJson = $this->get('jms_serializer')
            ->serializer($donor, 'json', SerializationContext::create()->setGroups(['FullDonor'])->setSerializeNull(true));

        return new Response($donorJson);
    }
}