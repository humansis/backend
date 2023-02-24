<?php

declare(strict_types=1);

namespace Component\Smartcard\Messaging\Handler;

use Component\Smartcard\Exception\SmartcardPurchaseRequestValidationErrorException;
use Component\Smartcard\Messaging\Message\SmartcardPurchaseMessage;
use Component\Smartcard\SmartcardPurchaseService;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use InputType\SmartcardPurchaseInputType;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsMessageHandler]
class SmartcardPurchaseMessageHandler
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly SmartcardPurchaseService $smartcardPurchaseService,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * @throws NonUniqueResultException
     * @throws EntityNotFoundException
     */
    public function __invoke(SmartcardPurchaseMessage $message): void
    {
        $smartcardPurchaseInputType = $this->getSmartcardPurchaseInputType($message);
        $this->smartcardPurchaseService->purchase(
            $message->getSmartcardNumber(),
            $smartcardPurchaseInputType
        );
    }

    /**
     * @throws SmartcardPurchaseRequestValidationErrorException
     */
    private function getSmartcardPurchaseInputType(SmartcardPurchaseMessage $message): SmartcardPurchaseInputType
    {
        try {
            $smartcardPurchaseInputType = $this->serializer->denormalize(
                $message->getPurchaseRequestBody(),
                SmartcardPurchaseInputType::class,
                null,
                [
                    AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                ]
            );
        } catch (NotNormalizableValueException $exception) {
            throw new SmartcardPurchaseRequestValidationErrorException(
                "Purchase request body could not be denormalized! " . $exception->getMessage()
            );
        }

        $validationErrors = $this->validator->validate($smartcardPurchaseInputType);
        if ($validationErrors->count() > 0) {
            throw new SmartcardPurchaseRequestValidationErrorException(
                "Purchase request body is not valid! Errors: " . $validationErrors
            );
        }

        return $smartcardPurchaseInputType;
    }
}
