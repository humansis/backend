<?php

declare(strict_types=1);

namespace Controller\VendorApp;

use Doctrine\Persistence\ManagerRegistry;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\SmartcardPurchaseInputType;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Entity\Smartcard;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Utils\SmartcardService;

class SmartcardController extends AbstractVendorAppController
{
    public function __construct(private readonly SerializerInterface $serializer, private readonly ValidatorInterface $validator, private readonly LoggerInterface $logger, private readonly SmartcardService $smartcardService, private readonly string $logsDir, private readonly ManagerRegistry $managerRegistry)
    {
    }
    /** @var */
    /**
     * //TODO whole endpoint should be removed after syncs of purchases
     *
     *
     *
     * @return JsonResponse
     * @throws Exception
     */
    #[Rest\Post('/vendor-app/v4/smartcards/{serialNumber}/purchase')]
    public function beneficiaries(Request $request): Response
    {
        $serializer = new Serializer([
            new DateTimeNormalizer(),
            new ObjectNormalizer(
                null,
                null,
                null,
                new ReflectionExtractor()
            ),
            new ArrayDenormalizer(),
        ]);

        $inputType = $serializer->denormalize($request->request->all(), SmartcardPurchaseInputType::class, null, [
            AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
        ]);

        $errors = $this->validator->validate($inputType);

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
            $purchase = $this->smartcardService->purchase($request->get('serialNumber'), $inputType);
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
     *
     *
     */
    #[Rest\Get('/vendor-app/v1/smartcards/blocked')]
    public function listOfBlocked(Request $request): Response
    {
        $country = $request->headers->get('country');
        $smartcards = $this->managerRegistry->getRepository(Smartcard::class)->findBlocked($country);

        return new JsonResponse($smartcards);
    }
}
