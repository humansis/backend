<?php declare(strict_types=1);

namespace NewApiBundle\Controller\VendorApp;

use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\SmartcardPurchaseInputType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use VoucherBundle\Repository\SmartcardRepository;

class SmartcardController extends AbstractVendorAppController
{
    /**
     * //TODO whole endpoint should be removed after syncs of purchases
     *
     * @Rest\Post("/vendor-app/v4/smartcards/{serialNumber}/purchase")
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function beneficiaries(Request $request): Response
    {
        /** @var SmartcardPurchaseInputType $data */
        $data = $this->get('serializer')->deserialize($request->getContent(), SmartcardPurchaseInputType::class, 'json');

        $errors = $this->get('validator')->validate($data);

        //TODO remove after syncs for purchases will be implemented
        if (count($errors) > 0) {
            $this->container->get('logger')->error('validation errors: '.((string) $errors).' data: '.json_encode($request->request->all()));

            $this->writeData(
                'purchaseV3',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all())
            );
            return new Response();
        }

        try {
            $purchase = $this->get('smartcard_service')->purchase($request->get('serialNumber'), $data);
        } catch (\Exception $exception) {
            $this->writeData(
                'purchaseV3',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all())
            );
            throw $exception;
        }

        return $this->json($purchase->getSmartcard());
    }

    /**
     * List of blocked smardcards.
     * Blocked smartcards are not allowed to pay with.
     *
     * @Rest\Get("/vendor-app/v1/smartcards/blocked")
     * @Security("is_granted('ROLE_VENDOR')")
     *
     * @param Request             $request
     * @param SmartcardRepository $smartcardRepository
     *
     * @return Response
     */
    public function listOfBlocked(Request $request, SmartcardRepository $smartcardRepository): Response
    {
        $country = $request->headers->get('country');
        $smartcards = $smartcardRepository->findBlocked($country);

        return new JsonResponse($smartcards);
    }

    private function writeData(string $type, string $user, string $smartcard, $data): void
    {
        $filename = $this->get('kernel')->getLogDir().'/';
        $filename .= implode('_', ['SC-invalidData', $type, 'vendor-'.$user, 'sc-'.$smartcard.'.json']);
        $logFile = fopen($filename, "a+");
        fwrite($logFile, $data);
        fclose($logFile);
    }
}
