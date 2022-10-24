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
    /** @var SerializerInterface */
    private $serializer;

    /** @var ValidatorInterface */
    private $validator;

    /** @var LoggerInterface */
    private $logger;

    /** @var SmartcardService */
    private $smartcardService;

    /** @var string */
    private $logsDir;

    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        SmartcardService $smartcardService,
        string $logsDir
    ) {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->smartcardService = $smartcardService;
        $this->logsDir = $logsDir;
    }
    /** @var  */
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
        $data = $this->serializer->deserialize(
            $request->getContent(),
            SmartcardPurchaseInputType::class,
            'json'
        );

        $errors = $this->validator->validate($data);

        //TODO remove after syncs for purchases will be implemented
        if (count($errors) > 0) {
            $this->logger->error(
                'validation errors: ' . ((string) $errors) . ' data: ' . json_encode($request->request->all())
            );

            $this->writeData(
                'purchaseV3',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all())
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
                json_encode($request->request->all())
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
