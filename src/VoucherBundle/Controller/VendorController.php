<?php

namespace VoucherBundle\Controller;


use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Serializer\SerializerInterface as Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UserBundle\Utils\UserService;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Entity\Booklet;
use UserBundle\Entity\User;
use VoucherBundle\Utils\VendorService;

/**
 * Class VendorController
 * @package VoucherBundle\Controller
 *
 * @SWG\Parameter(
 *     name="country",
 *     in="header",
 *     type="string",
 *     required=true
 * )
 */
class VendorController extends Controller
{

    private $userService;
    private $vendorService;

    /**
     * @param UserService $userService
     * @param VendorService $vendorService
     */
    public function __construct(UserService $userService,VendorService $vendorService)
    {
        $this->userService = $userService;
        $this->vendorService = $vendorService;
    }

    /**
     * Create a new Vendor. You must have called getSalt before use this one
     *
     * @Rest\Put("/vendors", name="add_vendor")
     * @Security("is_granted('ROLE_DISTRIBUTIONS_DIRECTOR')")
     *
     * @SWG\Tag(name="Vendors")
     *
     * @SWG\Parameter(
     *     name="vendor",
     *     in="body",
     *     required=true,
     *     @Model(type=Vendor::class, groups={"FullVendor"})
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Vendor created",
     *     @Model(type=Vendor::class)
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
        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');

        $vendorData = $request->request->all();

        try {
            $return = $this->vendorService->createFromArray($vendorData['__country'], $vendorData);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $vendorJson = $serializer->serialize(
            $return,
            'json',
            ['groups' => ['FullVendor']]
        );
        return new Response($vendorJson);
    }

    /**
     * Get all vendors
     *
     * @Rest\Get("/vendors", name="get_all_vendors")
     * @Security("is_granted('ROLE_USER')")
     *
     * @SWG\Tag(name="Vendors")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Vendors delivered",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Vendor::class, groups={"FullVendor"}))
     *     )
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
    public function getAllAction(Request $request)
    {
        try {
            $vendors = $this->vendorService->findAll($request->get('__country'));
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('serializer')->serialize($vendors, 'json', ['groups' => ['FullVendor']]);
        return new Response($json);
    }

    /**
     * Get single vendor
     *
     * @Rest\Get("/vendors/{id}", name="get_single_vendor")
     * @Security("is_granted('ROLE_USER')")
     * 
     * @SWG\Tag(name="Single Vendor")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Vendor delivered",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Vendor::class, groups={"FullVendor"}))
     *     )
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Vendor $vendor
     * @return Response
     */
    public function getSingleAction(Vendor $vendor)
    {
        $json = $this->get('serializer')->serialize($vendor, 'json', ['groups' => ['FullVendor']]);

        return new Response($json);
    }

    /**
     * Get single vendor.
     *
     * @Rest\Get("/vendor-app/v1/vendors/{id}")
     * @Security("is_granted('ROLE_USER')")
     *
     * @SWG\Tag(name="Single Vendor")
     * @SWG\Tag(name="Vendor App")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Vendor delivered",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Vendor::class, groups={"FullVendor"}))
     *     )
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Vendor $vendor
     *
     * @return Response
     */
    public function getSingleActionVendor(Vendor $vendor)
    {
        return $this->getSingleAction($vendor);
    }

    /**
     * Edit a vendor {id} with data in the body
     *
     * @Rest\Post("/vendors/{id}", name="update_vendor")
     * @Security("is_granted('ROLE_DISTRIBUTIONS_DIRECTOR')")
     *
     * @SWG\Tag(name="Vendors")
     *
     * @SWG\Parameter(
     *     name="vendor",
     *     in="body",
     *     type="string",
     *     required=true,
     *     description="fields of the vendor which must be updated",
     *     @Model(type=Vendor::class)
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="SUCCESS",
     *     @Model(type=Booklet::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @param Vendor $vendor
     * @return Response
     */
    public function updateAction(Request $request, Vendor $vendor)
    {
        $vendorData = $request->request->all();

        try {
            $newVendor = $this->vendorService->updateFromArray($vendorData['__country'], $vendor, $vendorData);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('serializer')->serialize($newVendor, 'json', ['groups' => ['FullVendor']]);
        return new Response($json);
    }


    /**
     * Archive a Vendor using their id
     *
     * @Rest\Post("/vendors/{id}/archive", name="archive_vendor")
     * @Security("is_granted('ROLE_DISTRIBUTIONS_DIRECTOR')")
     *
     * @SWG\Tag(name="Vendors")
     *
     * @SWG\Response(
     *     response=200,
     *     description="SUCCESS",
     *     @Model(type=User::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Vendor $vendor
     * @return Response
     */
    public function archiveAction(Vendor $vendor)
    {
        try {
            $archivedVendor = $this->vendorService->archiveVendor($vendor);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('serializer')->serialize($archivedVendor, 'json', ['groups' => ['FullVendor']]);
        return new Response($json);
    }


    /**
     * Delete an vendor with its links in the api
     * @Rest\Delete("/vendors/{id}", name="delete_vendor")
     * @Security("is_granted('ROLE_DISTRIBUTIONS_DIRECTOR')")
     *
     * @SWG\Tag(name="Vendors")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Success or not",
     *     @SWG\Schema(type="boolean")
     * )
     *
     * @param Vendor $vendor
     * @return Response
     */
    public function deleteAction(Vendor $vendor)
    {
        try {
            $isSuccess = $this->vendorService->deleteFromDatabase($vendor);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        
        return new Response(json_encode($isSuccess));
    }

    /**
     * Log a vendor with its username and salted password. Create a new one if not in the db (remove this part for prod env)
     *
     * @Rest\Post("/login_app", name="vendor_login")
     * @Rest\Post("/vendor-app/v1/login")
     *
     * @SWG\Tag(name="Vendor App")
     *
     * @SWG\Response(
     *      response=200,
     *      description="SUCCESS",
     *      examples={
     *          "application/json": {
     *              "at"="2018-01-12 12:11:05",
     *              "registered"="true",
     *              "user"="username"
     *          }
     *      }
     * )
     *
     * @SWG\Parameter(
     *     name="username",
     *     in="body",
     *     type="string",
     *     required=true,
     *     description="username of the vendor",
     *     @SWG\Schema()
     * )
     * @SWG\Parameter(
     *     name="salted_password",
     *     in="body",
     *     type="string",
     *     required=true,
     *     description="salted password of the vendor",
     *     @SWG\Schema()
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Bad credentials (username: myUsername)"
     * )
     *
     * @param Request       $request
     * @param Profiler|null $profiler
     *
     * @return Response
     */
    public function vendorLoginAction(Request $request, ?Profiler $profiler): Response
    {
        if (null !== $profiler) {
            $profiler->disable();
        }

        $username = $request->request->get('username');
        $saltedPassword = $request->request->get('salted_password');
        
        try {
            $user = $this->userService->login($username, $saltedPassword);
            $vendor = $this->vendorService->getVendorByUser($user);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_FORBIDDEN);
        }
        
        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');
        
        $vendorJson = $serializer->serialize($vendor, 'json', ['groups' => ['FullVendor']]);
        return new Response($vendorJson);
    }

    /**
     * To print a vendor's invoice
     *
     * @Rest\Get("/invoice-print/{id}", name="print_invoice")
     * @SWG\Tag(name="Vendors")
     *
     * @SWG\Response(
     *     response=200,
     *     description="SUCCESS",
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Vendor $vendor
     * @return Response
     */
    public function printInvoiceAction(Vendor $vendor)
    {
        try {
            return $this->vendorService->printInvoice($vendor);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
