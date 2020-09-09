<?php


namespace ProjectBundle\Controller;


use ProjectBundle\Entity\Donor;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class DonorController
 * @package ProjectBundle\Controller
 */
class DonorController extends Controller
{

    /**
     * @Rest\Get("/donors", name="get_all_donor")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
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

        $donorsJson = $this->get('serializer')
            ->serialize($donors, 'json', ['groups' => ['FullDonor'], 'datetime_format' => 'd-m-Y H:i:s']);
        return new Response($donorsJson);
    }

    /**
     * @Rest\Put("/donors", name="create_donor")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
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

        try {
            $donor = $this->get('project.donor_service')->create($donorArray);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $donorJson = $this->get('serializer')
            ->serialize($donor, 'json', ['groups' => ['FullDonor'], 'datetime_format' => 'd-m-Y H:i:s']);

        return new Response($donorJson);
    }

    /**
     * @Rest\Post("/donors/{id}", name="update_donor")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
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

        try {
            $donor = $this->get('project.donor_service')->edit($donor, $donorArray);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $donorJson = $this->get('serializer')
            ->serialize($donor, 'json', ['groups' => ['FullDonor'], 'datetime_format' => 'd-m-Y H:i:s']);

        return new Response($donorJson);
    }

    /**
     * Edit a donor
     * @Rest\Delete("/donors/{id}", name="delete_donor")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
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
        try {
            $valid = $this->get('project.donor_service')->delete($donor);
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

      /**
     * @Rest\Post("/donor/upload/logo", name="upload_donor_logo")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Donor")
     *
     * @SWG\Parameter(
     *     name="file",
     *     in="formData",
     *     required=true,
     *     type="file"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Image uploaded",
     *     @SWG\Schema(
     *          type="string"
     *     )
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function uploadLogoAction(Request $request)
    {
        $content = $request->getContent();
        $file = $request->files->get('file');

        $type = $file->getMimeType();
        if ($type !== 'image/gif' && $type !== 'image/jpeg' && $type !== 'image/png') {
            return new Response('The image type must be gif, png or jpg.', Response::HTTP_BAD_REQUEST);
        }

        $adapter = $this->container->get('knp_gaufrette.filesystem_map')->get('donors')->getAdapter();
        $filename = $this->get('common.upload_service')->uploadImage($file, $adapter);
        $bucketName = $this->getParameter('aws_s3_bucket_name');
        $region = $this->getParameter('aws_s3_region');

        $return = 'https://s3.'.$region.'.amazonaws.com/'.$bucketName.'/donors/'.$filename;
        return new Response(json_encode($return));
    }
}
