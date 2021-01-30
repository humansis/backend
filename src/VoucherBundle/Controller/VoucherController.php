<?php

namespace VoucherBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use DateTime;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\SerializerInterface as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Valid;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Entity\Voucher;
use VoucherBundle\Entity\VoucherPurchaseRecord;
use VoucherBundle\InputType\SmartcardRedemtionBatch;
use VoucherBundle\InputType\VoucherPurchase;
use VoucherBundle\InputType\VoucherRedemptionBatch;
use VoucherBundle\Repository\SmartcardPurchaseRepository;
use VoucherBundle\Repository\VoucherPurchaseRepository;
use VoucherBundle\Repository\VoucherRedemptionBatchRepository;
use VoucherBundle\Repository\VoucherRepository;
use VoucherBundle\Utils\VoucherService;

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
    /** @var VoucherService */
    private $voucherService;
    /** @var SerializerInterface */
    private $serializer;
    /** @var LoggerInterface */
    private $logger;

    /**
     * VoucherController constructor.
     *
     * @param VoucherService  $voucherService
     * @param Serializer      $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(VoucherService $voucherService, Serializer $serializer, LoggerInterface $logger)
    {
        $this->voucherService = $voucherService;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

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
     * @param Request        $request
     *
     * @return Response
     */
    public function createAction(Request $request)
    {
        /** @var Serializer $serializer */
        $serializer = $this->serializer;

        $voucherData = $request->request->all();

        try {
            $return = $this->voucherService->create($voucherData);
        } catch (Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $voucherJson = $serializer->serialize(
            $return,
            'json',
            ['groups' => ['FullVoucher'], 'datetime_format' => 'd-m-Y']
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
            $vouchers = $this->voucherService->findAll();
        } catch (Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->serializer->serialize($vouchers, 'json', ['groups' => ['FullVoucher'], 'datetime_format' => 'd-m-Y']);
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
     *         @SWG\Items(ref=@Model(type=VoucherPurchaseRecord::class, groups={"ValidatedAssistance"}))
     *     )
     * )
     * @SWG\Response(response=400, description="HTTP_BAD_REQUEST")
     *
     * @param Beneficiary $beneficiary
     *
     * @return Response
     */
    public function purchasedVoucherPurchases(Beneficiary $beneficiary)
    {
        $vouchers = $this->getDoctrine()->getRepository(VoucherPurchaseRecord::class)->findPurchasedByBeneficiary($beneficiary);

        $json = $this->serializer
            ->serialize($vouchers, 'json', ['groups' => ['ValidatedAssistance'], 'datetime_format' => 'd-m-Y H:m:i']);

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
        $json = $this->serializer->serialize($voucher, 'json', ['groups' => ['FullVoucher'], 'datetime_format' => 'd-m-Y']);

        return new Response($json);
    }


    /**
     * When a vendor sends their scanned vouchers.
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
     *
     * @return Response
     *
     * @deprecated endpoint does not support quantity
     */
    public function scanDeprecated(Request $request)
    {
        $vouchersData = $request->request->all();
        unset($vouchersData['__country']);

        $newVouchers = [];

        foreach ($vouchersData as $voucherData) {
            try {
                // This endpoint does accept value for all products, not for each one.
                // So, we set value for first product in list, other products will have value=null
                $value = false;

                $productData = [];
                foreach ($voucherData['productIds'] as $id) {
                    if (false === $value) {
                        $value = $voucherData['value'] ?? 0;
                    }

                    $productData[] = [
                        'id' => $id,
                        'value' => $value,
                        'quantity' => null,
                    ];

                    // after first product has set value, next products will have null value
                    $value = null;
                }

                $input = new VoucherPurchase();
                $input->setProducts($productData);
                $input->setVouchers([$voucherData['id']]);
                $input->setVendorId($voucherData['vendorId']);

                if (isset($voucherData['used_at'])) {
                    $input->setCreatedAt(new DateTime($voucherData['used_at']));
                }

                $voucherPurchase = $this->get('voucher.purchase_service')->purchase($input);

                $newVouchers[] = $voucherPurchase->getVouchers()->current();
            } catch (Exception $exception) {
                return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }

        $json = $this->serializer->serialize($newVouchers, 'json',
            ['groups' => ['FullVoucher'], 'datetime_format' => 'd-m-Y']);

        return new Response($json);
    }

    /**
     * Provide purchase of goods for vouchers.
     * If vendor scan some vouchers and sell some goods for them, this request will send.
     *
     * @Rest\Post("/vendor-app/v1/vouchers/purchase")
     * @Security("is_granted('ROLE_VENDOR')")
     *
     * @SWG\Tag(name="Vendor App")
     * @SWG\Parameter(name="purchase for vouchers",
     *     in="body",
     *     required=true,
     *     type="array",
     *     @SWG\Schema(ref=@Model(type="VoucherBundle\InputType\VoucherPurchase"))
     * )
     * @SWG\Response(response=200, description="SUCCESS")
     * @SWG\Response(response=400, description="BAD_REQUEST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function purchase(Request $request)
    {
        $this->logger->error('headers', $request->headers->all());
        $this->logger->error('content', [$request->getContent()]);

        $data = $this->serializer->deserialize($request->getContent(), VoucherPurchase::class.'[]', 'json');

        $errors = $this->get('validator')->validate($data, [
            new All([new Type(['type' => VoucherPurchase::class])]),
            new Valid(),
        ]);

        if (count($errors) > 0) {
            $this->logger->error('validation errors: '.((string) $errors));
            return new Response((string) $errors, Response::HTTP_BAD_REQUEST);
        }

        try {
            foreach ($data as $item) {
                $this->get('voucher.purchase_service')->purchase($item);
            }

            return new Response(json_encode(true));
        } catch (EntityNotFoundException $ex) {
            $this->logger->error('Entity not found: ', [$ex->getMessage()]);
            return new Response($ex->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Rest\Get("/vouchers/purchases/redemption-batch/{id}", name="voucher_redemption_batch")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @param \VoucherBundle\Entity\VoucherRedemptionBatch $redemptionBatch
     * @return JsonResponse
     */
    public function getRedemptionBatch(\VoucherBundle\Entity\VoucherRedemptionBatch $redemptionBatch): JsonResponse
    {
        return $this->json($redemptionBatch);
    }

    /**
     * Get vendor redeemed batches
     *
     * @Rest\Get("/vouchers/purchases/redeemed-batches/{id}", name="vouchers_redeemed_batches")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Vouchers")
     * @SWG\Tag(name="Single Vendor")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All vendor redeemed vouchers",
     * )
     *
     * @param Vendor $vendor
     *
     * @return Response
     */
    public function getRedeemedBatches(Vendor $vendor): Response
    {
        /** @var VoucherRedemptionBatchRepository $repository */
        $repository = $this->getDoctrine()->getManager()
            ->getRepository(\VoucherBundle\Entity\VoucherRedemptionBatch::class);

        return $this->json($repository->getAllByVendor($vendor));
    }

    /**
     * Set vendor purchase as redeemed
     *
     * @Rest\Post("/vouchers/purchases/redeem-check/{id}", name="vouchers_redeem_batch_check")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Vouchers")
     * @SWG\Tag(name="Single Vendor")
     *
     * @SWG\Parameter(
     *     name="vendor",
     *     in="body",
     *     type="array",
     *     required=true,
     *     description="fields of the vendor voucher ids",
     *     schema="int"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="All vendor purchases"
     * )
     *
     * @param Vendor                 $vendor
     *
     * @param VoucherRedemptionBatch $newBatch
     *
     * @return Response
     */
    public function checkRedemptionBatch(Vendor $vendor, VoucherRedemptionBatch $newBatch): Response
    {
        return $this->json($this->voucherService->checkBatch($newBatch, $vendor));
    }

    /**
     * Set vendor purchase as redeemed
     *
     * @Rest\Post("/vouchers/purchases/redeem-batch/{id}", name="vouchers_redeem_batch")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Vouchers")
     * @SWG\Tag(name="Single Vendor")
     *
     * @SWG\Parameter(
     *     name="vendor",
     *     in="body",
     *     type="array",
     *     required=true,
     *     description="fields of the vendor voucher ids",
     *     schema="int"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="All vendor purchases"
     * )
     *
     * @param Vendor                 $vendor
     *
     * @param VoucherRedemptionBatch $newBatch
     *
     * @return Response
     */
    public function redeemBatch(Vendor $vendor, VoucherRedemptionBatch $newBatch): Response
    {
        $check = $this->voucherService->checkBatch($newBatch, $vendor);

        if ($check->hasInvalidVouchers()) {
            $message = $check->jsonSerialize();
            $message['message'] = "There are invalid vouchers";
            return new Response(json_encode($message), Response::HTTP_BAD_REQUEST);
        }
        if (count($check->getValidVouchers()) == 0) {
            $message = $check->jsonSerialize();
            $message['message'] = "There are no valid vouchers";
            return new Response(json_encode($message), Response::HTTP_BAD_REQUEST);
        }

        $redeemedBatch = $this->voucherService->redeemBatch($vendor, $newBatch, $this->getUser());

        return $this->json($redeemedBatch);
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
     * @param Voucher        $voucher
     *
     * @return Response
     */
    public function deleteAction(Voucher $voucher)
    {
        try {
            $isSuccess = $this->voucherService->deleteOneFromDatabase($voucher);
        } catch (Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(json_encode($isSuccess));
    }

    /**
     * This endpoint actually deletes all vouchers in provided Booklet
     *
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
     * @param Booklet        $booklet
     *
     * @return Response
     */
    public function deleteBatchAction(Booklet $booklet)
    {
        try {
            $isSuccess = $this->voucherService->deleteBatchVouchers($booklet);
        } catch (Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        
        return new Response(json_encode($isSuccess));
    }
}
