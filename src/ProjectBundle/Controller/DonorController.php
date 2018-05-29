<?php


namespace ProjectBundle\Controller;


use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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
    }
}