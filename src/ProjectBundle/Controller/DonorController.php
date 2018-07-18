<?php


namespace ProjectBundle\Controller;

use JMS\Serializer\SerializationContext;
use ProjectBundle\Entity\Donor;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

class DonorController extends Controller
{

    /**
     * @Rest\Get("/donors", name="get_all_donor")
     *
     * @SWG\Tag(name="Donors")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All donors",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Donor::class))
     *     )
     * )
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
     *
     * @Rest\Get("/donors/{id}", name="show_donor")
     *
     * @SWG\Tag(name="Donors")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Donor asked",
     *     @Model(type=Donor::class)
     * )
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
     * @SWG\Tag(name="Donors")
     *
     * @SWG\Parameter(
     *     name="donor",
     *     in="body",
     *     required=true,
     *     @Model(type=Donor::class, groups={"FullDonor"})
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Donor created",
     *     @Model(type=Donor::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
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
     *
     * @SWG\Tag(name="Donors")
     *
     * @SWG\Parameter(
     *     name="donor",
     *     in="body",
     *     required=true,
     *     @Model(type=Donor::class, groups={"FullDonor"})
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Donor updated",
     *     @Model(type=Donor::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
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

    /**
     * Edit a donor
     * @Rest\Delete("/donors/{id}", name="delete_donor")
     *
     * @SWG\Tag(name="Donors")
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
     * @param Donor $donor
     * @return Response
     */
    public function deleteAction(Donor $donor)
    {
        try
        {
            $valid = $this->get('project.donor_service')->delete($donor);
        }
        catch (\Exception $e)
        {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        if ($valid)
            return new Response("", Response::HTTP_OK);
        if (!$valid)
            return new Response("", Response::HTTP_BAD_REQUEST);
    }
}