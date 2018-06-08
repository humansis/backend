<?php


namespace ProjectBundle\Controller;

use JMS\Serializer\SerializationContext;
use ProjectBundle\Entity\Donor;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DonorController extends Controller
{

    /**
     * @Rest\Get("/donors", name="get_all_donor")
     *
     * @return Response
     */
    public function getAllAction()
    {
        $donors = $this->get('project.donor_service')->findAll();

        $donorsJson = $this->get('jms_serializer')
            ->serialize($donors, 'json', SerializationContext::create()->setGroups(['FullDonor'])->setSerializeNull(true));
        return new Response($donorsJson);
    }

    /**
     * Get a donor
     * @Rest\Get("/donors/{id}", name="show_donor")
     *
     * @param Donor $donor
     * @return Response
     */
    public function showAction(Donor $donor)
    {
        $json = $this->get('jms_serializer')
            ->serialize($donor, 'json', SerializationContext::create()->setGroups(['FullDonor'])->setSerializeNull(true));

        return new Response($json, Response::HTTP_OK);
    }

    /**
     * @Rest\Put("/donors", name="create_donor")
     *
     * @param Request $request
     * @return Response
     */
    public function createAction(Request $request)
    {
        $donorArray = $request->request->all();

        try
        {
            $donor = $this->get('project.donor_service')->create($donorArray);
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $donorJson = $this->get('jms_serializer')
            ->serialize($donor, 'json', SerializationContext::create()->setGroups(['FullDonor'])->setSerializeNull(true));

        return new Response($donorJson);
    }

    /**
     * @Rest\Post("/donors/{id}", name="update_donor")
     *
     * @param Request $request
     * @param Donor $donor
     * @return Response
     */
    public function updateAction(Request $request, Donor $donor)
    {
        $donorArray = $request->request->all();

        try
        {
            $donor = $this->get('project.donor_service')->edit($donor, $donorArray);
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $donorJson = $this->get('jms_serializer')
            ->serialize($donor, 'json', SerializationContext::create()->setGroups(['FullDonor'])->setSerializeNull(true));

        return new Response($donorJson);
    }
}