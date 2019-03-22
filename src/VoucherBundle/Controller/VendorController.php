<?php

namespace VoucherBundle\Controller;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use VoucherBundle\Entity\Vendor;

/**
 * Class VendorController
 * @package VoucherBundle\Controller
 */
class VendorController extends Controller
{
    /**
     * Create a new Vendor. You must have called getSalt before use this one
     *
     * @Rest\Put("/vendors", name="add_vendor")
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
    public function createVendorAction(Request $request)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $vendorData = $request->request->all();

        try {
            $return = $this->get('voucher.vendor_service')->create($vendorData);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $vendorJson = $serializer->serialize(
            $return,
            'json',
            SerializationContext::create()->setGroups(['FullVendor'])->setSerializeNull(true)
        );
        return new Response($vendorJson);
    }

    /**
     * Get all vendors
     *
     * @Rest\Get("/vendors", name="get_all_vendors")
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
            $vendors = $this->get('voucher.vendor_service')->findAll();
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('jms_serializer')->serialize($vendors, 'json', SerializationContext::create()->setGroups(['FullVendor'])->setSerializeNull(true));
        return new Response($json);
    }

    /**
     * Get single vendor
     *
     * @Rest\Get("/vendors/{id}", name="get_single_vendor")
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
    public function getSingleVendorAction(Vendor $vendor)
    {
        $json = $this->get('jms_serializer')->serialize($vendor, 'json', SerializationContext::create()->setGroups(['FullVendor'])->setSerializeNull(true));

        return new Response($json);
    }


    /**
     * Edit a vendor {id} with data in the body
     *
     * @Rest\Post("/vendors/{id}", name="update_vendor")
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
            $newVendor = $this->get('voucher.vendor_service')->update($vendor, $vendorData);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('jms_serializer')->serialize($newVendor, 'json', SerializationContext::create()->setGroups(['FullVendor'])->setSerializeNull(true));
        return new Response($json);
    }


    /**
     * Archive a Vendor using their id
     *
     * @Rest\Post("/vendors/{id}/archive", name="archive_vendor")
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
    public function archiveVendorAction(Vendor $vendor)
    {
        try {
            $archivedVendor = $this->get('voucher.vendor_service')->archiveVendor($vendor);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('jms_serializer')->serialize($archivedVendor, 'json', SerializationContext::create()->setGroups(['FullVendor'])->setSerializeNull(true));
        return new Response($json);
    }


    /**
     * Delete an vendor with its links in the api
     * @Rest\Delete("/vendors/{id}", name="delete_vendor")
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
            $isSuccess = $this->get('voucher.vendor_service')->deleteFromDatabase($vendor);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        
        return new Response(json_encode($isSuccess));
    }

    /**
     * Log a vendor with its username and salted password. Create a new one if not in the db (remove this part for prod env)
     * @Rest\Post("/login_app", name="vendor_login")
     *
     * @SWG\Tag(name="Vendors")
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
     * @param Request $request
     * @return Response
     */
    public function loginAction(Request $request)
    {
        $username = $request->request->get('username');
        $saltedPassword = $request->request->get('salted_password');
        
        try
        {
            $user = $this->container->get('user.user_service')->login($username, $saltedPassword, null);
            $vendor = $this->container->get('voucher.vendor_service')->login($user);
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage(), Response::HTTP_FORBIDDEN);
        }
        
        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');
        
        $vendorJson = $serializer->serialize($vendor, 'json', SerializationContext::create()->setGroups(['FullVendor'])->setSerializeNull(true));
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
            return $this->get('voucher.vendor_service')->printInvoice($vendor);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
