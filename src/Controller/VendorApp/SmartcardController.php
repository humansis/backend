<?php

declare(strict_types=1);

namespace Controller\VendorApp;

use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\SmartcardPurchaseInputType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Entity\Smartcard;

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
     * @throws Exception
     */
    public function beneficiaries(Request $request): Response
    {
        /** @var SmartcardPurchaseInputType $data */
        $data = $this->get('serializer')->deserialize($request->getContent(), SmartcardPurchaseInputType::class, 'json');

        $errors = $this->get('validator')->validate($data);

        //TODO remove after syncs for purchases will be implemented
        if (count($errors) > 0) {
            $this->container->get('logger')->error('validation errors: ' . ((string) $errors) . ' data: ' . json_encode($request->request->all()));

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
        } catch (Exception $exception) {
            $this->writeData(
                'purchaseV3',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all())
            );
            throw $exception;
        }

        $json = $this->get('serializer')->serialize($purchase->getSmartcard(), 'json', ['groups' => ['SmartcardOverview']]);

        return new Response($json);
    }

    private function writeData(string $type, string $user, string $smartcard, $data): void
    {
        $filename = $this->get('kernel')->getLogDir() . '/';
        $filename .= implode('_', ['SC-invalidData', $type, 'vendor-' . $user, 'sc-' . $smartcard . '.json']);
        $logFile = fopen($filename, "a+");
        fwrite($logFile, $data);
        fclose($logFile);
    }

    /**
     * List of blocked smardcards.
     * Blocked smartcards are not allowed to pay with.
     *
     * @Rest\Get("/vendor-app/v1/smartcards/blocked")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function listOfBlocked(Request $request): Response
    {
        $country = $request->headers->get('country');
        $smartcards = $this->getDoctrine()->getRepository(Smartcard::class)->findBlocked($country);

        return new JsonResponse($smartcards);
    }
}
