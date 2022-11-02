<?php

declare(strict_types=1);

namespace Controller\VendorApp;

use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\SmartcardPurchaseInputType;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Entity\Smartcard;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Utils\SmartcardService;

class SmartcardController extends AbstractVendorAppController
{
    public function __construct(private readonly SerializerInterface $serializer, private readonly ValidatorInterface $validator, private readonly LoggerInterface $logger, private readonly SmartcardService $smartcardService, private readonly string $logsDir)
    {
    }
    /** @var */
    /**
     * //TODO whole endpoint should be removed after syncs of purchases
     *
     * @Rest\Post("/vendor-app/v4/smartcards/{serialNumber}/purchase")
     *
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function beneficiaries(Request $request): Response
    {
        /** @var SmartcardPurchaseInputType $data */
        $data = $this->serializer->deserialize(
            $request->getContent(),
            SmartcardPurchaseInputType::class,
            'json'
        );

        $errors = $this->validator->validate($data);

        //TODO remove after syncs for purchases will be implemented
        if (count($errors) > 0) {
            $this->logger->error(
                'validation errors: ' . ((string) $errors) . ' data: ' . json_encode($request->request->all(), JSON_THROW_ON_ERROR)
            );

            $this->writeData(
                'purchaseV3',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all(), JSON_THROW_ON_ERROR)
            );

            return new Response();
        }

        try {
            $purchase = $this->smartcardService->purchase($request->get('serialNumber'), $data);
        } catch (Exception $exception) {
            $this->writeData(
                'purchaseV3',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all(), JSON_THROW_ON_ERROR)
            );
            throw $exception;
        }

        $json = $this->serializer->serialize(
            $purchase->getSmartcard(),
            'json',
            ['groups' => ['SmartcardOverview']]
        );

        return new Response($json);
    }

    private function writeData(string $type, string $user, string $smartcard, $data): void
    {
        $filename = $this->logsDir . '/';
        $filename .= implode('_', ['SC-invalidData', $type, 'vendor-' . $user, 'sc-' . $smartcard . '.json']);
        $logFile = fopen($filename, "a+");
        fwrite($logFile, (string) $data);
        fclose($logFile);
    }

    /**
     * List of blocked smardcards.
     * Blocked smartcards are not allowed to pay with.
     *
     * @Rest\Get("/vendor-app/v1/smartcards/blocked")
     *
     *
     */
    public function listOfBlocked(Request $request): Response
    {
        $country = $request->headers->get('country');
        $smartcards = $this->getDoctrine()->getRepository(Smartcard::class)->findBlocked($country);

        return new JsonResponse($smartcards);
    }
}
