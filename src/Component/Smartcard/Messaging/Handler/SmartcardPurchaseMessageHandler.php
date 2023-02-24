<?php

declare(strict_types=1);

namespace Component\Smartcard\Messaging\Handler;

use Component\Smartcard\Exception\SmartcardPurchaseAssistanceNotFoundException;
use Component\Smartcard\Exception\SmartcardPurchaseBeneficiaryNotFoundException;
use Component\Smartcard\Exception\SmartcardPurchaseProductNotFoundException;
use Component\Smartcard\Exception\SmartcardPurchaseRequestValidationErrorException;
use Component\Smartcard\Exception\SmartcardPurchaseVendorNotFoundException;
use Component\Smartcard\Messaging\Message\SmartcardPurchaseMessage;
use Component\Smartcard\SmartcardPurchaseService;
use Doctrine\ORM\EntityNotFoundException;
use InputType\SmartcardPurchaseInputType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsMessageHandler]
class SmartcardPurchaseMessageHandler
{
    private readonly Serializer $serializer;

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly SmartcardPurchaseService $smartcardPurchaseService,
        private readonly LoggerInterface $logger
    ) {
        $this->serializer = new Serializer([
            new DateTimeNormalizer(),
            new ObjectNormalizer(
                propertyTypeExtractor: new ReflectionExtractor()
            ),
            new ArrayDenormalizer(),
        ]);
    }

    /**
     * @throws SmartcardPurchaseVendorNotFoundException
     * @throws SmartcardPurchaseBeneficiaryNotFoundException
     * @throws SmartcardPurchaseProductNotFoundException
     * @throws SmartcardPurchaseAssistanceNotFoundException
     * @throws SmartcardPurchaseRequestValidationErrorException
     * @throws EntityNotFoundException
     */
    public function __invoke(SmartcardPurchaseMessage $message): void
    {
        $this->logger->info("Smartcard purchase: Consuming message from queue");

        $smartcardPurchaseInputType = $this->getSmartcardPurchaseInputType($message);
        $smartcardPurchase = $this->smartcardPurchaseService->purchase(
            $message->getSmartcardNumber(),
            $smartcardPurchaseInputType
        );

        $this->logger->info(
            "Smartcard purchase: Purchase with ID {$smartcardPurchase->getId()} was successfully processed"
        );
    }

    /**
     * @throws SmartcardPurchaseRequestValidationErrorException
     */
    private function getSmartcardPurchaseInputType(SmartcardPurchaseMessage $message): SmartcardPurchaseInputType
    {
        $smartcardPurchaseInputType = $this->serializer->denormalize(
            $message->getPurchaseRequestBody(),
            SmartcardPurchaseInputType::class,
            null,
            [
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
            ]
        );

        $validationErrors = $this->validator->validate($smartcardPurchaseInputType);

        if ($validationErrors->count() > 0) {
            $this->logger->error(
                "Smartcard purchase failed: Purchase request body is not valid! Errors: " . $validationErrors
            );
            throw new SmartcardPurchaseRequestValidationErrorException(
                "Purchase request body is not valid! Errors: " . $validationErrors
            );
        }

        return $smartcardPurchaseInputType;
    }
}
