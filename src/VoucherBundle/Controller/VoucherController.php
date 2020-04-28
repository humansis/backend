<?php

namespace VoucherBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\Voucher;
use VoucherBundle\Entity\VoucherRecord;
use VoucherBundle\Exception\FixedValidationException;

/**
 * Class VoucherController
 * @package VoucherBundle\Controller
 *
 * @SWG\Parameter(
 *     name="country",
 *     in="header",
 *     type="string",
 *     required=true
 * )
 */
class VoucherController extends Controller
{
    /**
     * Create a new Voucher.
     *
     * @Rest\Put("/vouchers", name="add_voucher")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Vouchers")
     *
     * @SWG\Parameter(
     *     name="voucher",
     *     in="body",
     *     required=true,
     *     @Model(type=Voucher::class, groups={"FullVoucher"})
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Voucher created",
     *     @Model(type=Voucher::class)
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
        $serializer = $this->get('jms_serializer');

        $voucherData = $request->request->all();

        if (!array_key_exists('currency', $voucherData)) {
            return new Response('Currency missing', Response::HTTP_BAD_REQUEST);
        } elseif (0 == preg_match('/^[A-Z][A-Z][A-Z]$/', $voucherData['currency'])) {
            return new Response('Wrong currency format', Response::HTTP_BAD_REQUEST);
        }
        if (!array_key_exists('number_vouchers', $voucherData)) {
            return new Response('Voucher count missing', Response::HTTP_BAD_REQUEST);
        } elseif (!is_integer($voucherData['number_vouchers']) || $voucherData['number_vouchers'] < 1) {
            return new Response('Wrong voucher count format', Response::HTTP_BAD_REQUEST);
        }
        if (!array_key_exists('booklet', $voucherData)) {
            return new Response('booklet missing', Response::HTTP_BAD_REQUEST);
        } elseif ($voucherData['booklet']->getCurrency() !== $voucherData['currency']) {
            return new Response('Inconsistency in Booklet currency and voucher currency', Response::HTTP_BAD_REQUEST);
        }
        if (!array_key_exists('bookletCode', $voucherData)) {
            return new Response('bookletCode missing', Response::HTTP_BAD_REQUEST);
        } elseif ($voucherData['booklet']->getCode() !== $voucherData['bookletCode']) {
            return new Response('Inconsistency in Booklet->code and voucher bookletCode', Response::HTTP_BAD_REQUEST);
        }
        if (!array_key_exists('values', $voucherData) || empty($voucherData['values'])) {
            return new Response('Values missing', Response::HTTP_BAD_REQUEST);
        } else {
            foreach ($voucherData['values'] as $value) {
                if (!is_integer($value)) {
                    return new Response('Values must be integers', Response::HTTP_BAD_REQUEST);
                }
            }
        }

        try {
            $return = $this->get('voucher.voucher_service')->create($voucherData);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $voucherJson = $serializer->serialize(
            $return,
            'json',
            SerializationContext::create()->setGroups(['FullVoucher'])->setSerializeNull(true)
        );

        return new Response($voucherJson);
    }

    /**
     * Get all vouchers
     *
     * @Rest\Get("/vouchers", name="get_all_vouchers")
     * @Security("is_granted('ROLE_USER')")
     *
     * @SWG\Tag(name="Vouchers")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Vouchers delivered",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Voucher::class, groups={"FullVoucher"}))
     *     )
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @return Response
     */
    public function getAllAction()
    {
        try {
            $vouchers = $this->get('voucher.voucher_service')->findAll();
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('jms_serializer')->serialize($vouchers, 'json', SerializationContext::create()->setGroups(['FullVoucher'])->setSerializeNull(true));
        return new Response($json);
    }

    /**
     * Get purchased vouchers by beneficiary
     *
     * @Rest\Get("/vouchers/purchased/{beneficiaryId}")
     * @ParamConverter("beneficiary", options={"mapping": {"beneficiaryId" : "id"}})
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Vouchers")
     * @SWG\Parameter(name="beneficiaryId",
     *     in="path",
     *     type="integer",
     *     required=true
     * )
     * @SWG\Response(
     *     response=200,
     *     description="List of purchased vouchers",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=VoucherRecord::class, groups={"ValidatedDistribution"}))
     *     )
     * )
     * @SWG\Response(response=400, description="HTTP_BAD_REQUEST")
     *
     * @param Beneficiary $beneficiary
     * @return Response
     */
    public function purchasedVoucherRecords(Beneficiary $beneficiary)
    {
        $vouchers = $this->getDoctrine()->getRepository(VoucherRecord::class)->findPurchasedByBeneficiary($beneficiary);

        $json = $this->get('jms_serializer')
            ->serialize($vouchers, 'json', SerializationContext::create()->setSerializeNull(true)->setGroups(["ValidatedDistribution"]));

        return new Response($json);
    }


    /**
     * Get single voucher
     *
     * @Rest\Get("/vouchers/{id}", name="get_single_voucher")
     * @Security("is_granted('ROLE_USER')")
     *
     * @SWG\Tag(name="Single Voucher")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Voucher delivered",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Voucher::class, groups={"FullVoucher"}))
     *     )
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Voucher $voucher
     * @return Response
     */
    public function getSingleVoucherAction(Voucher $voucher)
    {
        $json = $this->get('jms_serializer')->serialize($voucher, 'json', SerializationContext::create()->setGroups(['FullVoucher'])->setSerializeNull(true));

        return new Response($json);
    }


    /**
     * When a vendor sends their scanned vouchers
     *
     * @Rest\Post("/vouchers/scanned", name="scanned_vouchers")
     * @Security("is_granted('ROLE_VENDOR')")
     * @SWG\Tag(name="Vouchers")
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
     * @param Request $request
     * @return Response
     * @deprecated endpoint does not support quantity
     */
    public function scanDeprecated(Request $request)
    {
        $vouchersData = $request->request->all();
        unset($vouchersData['__country']);
        $newVouchers = [];

        foreach ($vouchersData as $voucherData) {
            try {
                $newVoucher = $this->get('voucher.voucher_service')->scannedDeprecated($voucherData, $this->getUser());
                $newVouchers[] = $newVoucher;
            } catch (\Exception $exception) {
                return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }

        $json = $this->get('jms_serializer')->serialize($newVouchers, 'json', SerializationContext::create()->setGroups(['FullVoucher'])->setSerializeNull(true));
        return new Response($json);
    }

    /**
     * When a vendor sends their scanned vouchers
     *
     * @Rest\Post("/vendor-app/v1/vouchers/scanned")
     * @Security("is_granted('ROLE_VENDOR')")
     *
     * @SWG\Tag(name="Vendor App")
     * @SWG\Parameter(name="scanned voucher",
     *     in="body",
     *     required=true,
     *     @Model(type=\VoucherBundle\Annotation\VoucherScanned::class)
     * )
     * @SWG\Response(response=200, description="SUCCESS")
     * @SWG\Response(response=400, description="BAD_REQUEST")
     *
     * @param Request $request
     * @return Response
     */
    public function scan(Request $request)
    {
        try {
            $vouchersData = $request->request->all();
            unset($vouchersData['__country']);

            $newVouchers = [];
            foreach ($vouchersData as $voucherData) {
                try {
                    $this->get('request_validator')->validate(
                        "voucher_scanned",
                        \VoucherBundle\Constraints\VoucherScannedConstraints::class,
                        $voucherData
                    );
                } catch (ValidationException $exception) {
                    throw new FixedValidationException($exception);
                }

                $newVouchers[] = $this->get('voucher.voucher_service')->scanned($voucherData);
            }

            $json = $this->get('jms_serializer')->serialize($newVouchers, 'json', SerializationContext::create()->setGroups(['FullVoucher'])->setSerializeNull(true));
            return new Response($json);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * When a voucher is returned to humanitarian
     *
     * @Rest\Post("/vouchers/redeem", name="redeemed_vouchers")
     * @Security("is_granted('ROLE_VENDOR')")
     * @SWG\Tag(name="Vouchers")
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
     * @param Request $request
     * @return Response
     */
    public function redeemAction(Request $request)
    {
        $voucherData = $request->request->all();
        unset($voucherData['__country']);

        if (!isset($voucherData['id'])) {
            return new Response("Missing parameter 'id'", Response::HTTP_BAD_REQUEST);
        }

        /** @var Voucher|null $voucher */
        $voucher = $this->getDoctrine()->getRepository(Voucher::class)->find($voucherData['id']);

        if (!$voucher) {
            return new Response("There is no voucher with id '{$voucherData['id']}'", Response::HTTP_BAD_REQUEST);
        }
        try {
            $this->get('voucher.voucher_service')->redeem($voucher, $this->getUser());
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('jms_serializer')->serialize($voucher, 'json', SerializationContext::create()->setGroups(['FullVoucher'])->setSerializeNull(true));
        return new Response($json);
    }

    /**
     * Delete a booklet
     * @Rest\Delete("/vouchers/{id}", name="delete_voucher")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Vouchers")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Success or not",
     *     @SWG\Schema(type="boolean")
     * )
     *
     * @param Voucher $voucher
     * @return Response
     */
    public function deleteAction(Voucher $voucher)
    {
        try {
            $isSuccess = $this->get('voucher.voucher_service')->deleteOneFromDatabase($voucher);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(json_encode($isSuccess));
    }


    /**
     * Delete a batch of vouchers
     * @Rest\Delete("/vouchers/delete_batch/{id}", name="delete_batch_vouchers")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     * @SWG\Tag(name="Vouchers")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Success or not",
     *     @SWG\Schema(type="boolean")
     * )
     *
     * @param Booklet $booklet
     * @return Response
     */
    public function deleteBatchAction(Booklet $booklet)
    {
        try {
            $isSuccess = $this->get('voucher.voucher_service')->deleteBatchVouchers($booklet);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        
        return new Response(json_encode($isSuccess));
    }
}
