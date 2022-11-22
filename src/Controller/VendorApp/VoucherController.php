<?php

namespace Controller\VendorApp;

use Doctrine\ORM\EntityNotFoundException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Model\PurchaseService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Valid;
use InputType\VoucherPurchase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Utils\SmartcardService;

class VoucherController extends Controller
{
    /** @var SerializerInterface */
    private $serializer;

    /** @var ValidatorInterface */
    private $validator;

    /** @var LoggerInterface */
    private $logger;

    /** @var PurchaseService */
    private $purchaseService;

    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        PurchaseService $purchaseService
    ) {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->purchaseService = $purchaseService;
    }

    /**
     * Provide purchase of goods for vouchers.
     * If vendor scan some vouchers and sell some goods for them, this request will send.
     *
     * @Rest\Post("/vendor-app/v1/vouchers/purchase")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function purchase(Request $request)
    {
        $data = $this->serializer->deserialize($request->getContent(), VoucherPurchase::class . '[]', 'json');

        $errors = $this->validator->validate($data, [
            new All([new Type(['type' => VoucherPurchase::class])]),
            new Valid(),
        ]);

        if (count($errors) > 0) {
            $this->logger->error('validation errors: ' . ((string) $errors));

            return new Response((string) $errors, Response::HTTP_BAD_REQUEST);
        }

        try {
            foreach ($data as $item) {
                $this->purchaseService->purchase($item);
            }

            return new Response(json_encode(true));
        } catch (EntityNotFoundException $ex) {
            $this->logger->error('Entity not found: ', [$ex->getMessage()]);

            return new Response($ex->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
