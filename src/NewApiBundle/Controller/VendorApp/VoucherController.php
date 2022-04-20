<?php declare(strict_types=1);

namespace NewApiBundle\Controller\VendorApp;

use Doctrine\ORM\EntityNotFoundException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Valid;
use VoucherBundle\InputType\VoucherPurchase;
use VoucherBundle\Model\PurchaseService;
use VoucherBundle\Utils\VoucherService;

class VoucherController extends AbstractVendorAppController
{
    /** @var PurchaseService */
    private $voucherPurchaseService;

    /**
     * @param PurchaseService $voucherPurchaseService
     */
    public function __construct(PurchaseService $voucherPurchaseService)
    {
        $this->voucherPurchaseService = $voucherPurchaseService;
    }

    /**
     * Provide purchase of goods for vouchers.
     * If vendor scan some vouchers and sell some goods for them, this request will send.
     *
     * @Rest\Post("/vendor-app/v1/vouchers/purchase")
     * @Security("is_granted('ROLE_VENDOR')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function purchase(Request $request): Response
    {
        $data = $this->get('serializer')->deserialize($request->getContent(), VoucherPurchase::class.'[]', 'json');

        $errors = $this->get('validator')->validate($data, [
            new All([new Type(['type' => VoucherPurchase::class])]),
            new Valid(),
        ]);

        if (count($errors) > 0) {
            $this->container->get('logger')->error('validation errors: '.((string) $errors));
            return new Response((string) $errors, Response::HTTP_BAD_REQUEST);
        }

        try {
            foreach ($data as $item) {
                $this->voucherPurchaseService->purchase($item);
            }

            return new Response(json_encode(true));
        } catch (EntityNotFoundException $ex) {
            $this->container->get('logger')->error('Entity not found: ', [$ex->getMessage()]);
            return new Response($ex->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
