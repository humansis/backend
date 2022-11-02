<?php

namespace Controller\VendorApp;

use Doctrine\ORM\EntityNotFoundException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Model\PurchaseService;
use Psr\Log\LoggerInterface;
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

class VoucherController extends AbstractVendorAppController
{
    public function __construct(private readonly SerializerInterface $serializer, private readonly ValidatorInterface $validator, private readonly LoggerInterface $logger, private readonly PurchaseService $purchaseService)
    {
    }

    /**
     * Provide purchase of goods for vouchers.
     * If vendor scan some vouchers and sell some goods for them, this request will send.
     *
     * @Rest\Post("/vendor-app/v1/vouchers/purchase")
     *
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

            return new Response(json_encode(true, JSON_THROW_ON_ERROR));
        } catch (EntityNotFoundException $ex) {
            $this->logger->error('Entity not found: ', [$ex->getMessage()]);

            return new Response($ex->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
