<?php

declare(strict_types=1);

namespace Component\Smartcard\Messaging\Handler;

use Component\Smartcard\Exception\SmartcardDepositReliefPackageCanNotBeDistributedException;
use Component\Smartcard\Exception\SmartcardDepositRequestValidationErrorException;
use Component\Smartcard\Messaging\Message\SmartcardDepositMessage;
use Component\Smartcard\SmartcardDepositService;
use Doctrine\ORM\EntityNotFoundException;
use InputType\RequestConverter;
use InputType\Smartcard\DepositInputType;
use InputType\SynchronizationBatch\CreateDepositInputType;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsMessageHandler]
class SmartcardDepositMessageHandler
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly SmartcardDepositService $smartcardDepositService
    ) {
    }

    /**
     * @throws EntityNotFoundException
     * @throws InvalidArgumentException
     * @throws SmartcardDepositReliefPackageCanNotBeDistributedException
     */
    public function __invoke(SmartcardDepositMessage $message): void
    {
        $depositInput = $this->getSmartcardDepositInputType($message);

        $this->smartcardDepositService->processDeposit(
            $message->getUserId(),
            $message->getSmartcardNumber(),
            $depositInput
        );
    }

    /**
     * @throws SmartcardDepositRequestValidationErrorException
     */
    private function getSmartcardDepositInputType(SmartcardDepositMessage $message): DepositInputType
    {
        return match ($message->getInputTypeClass()) {
            DepositInputType::class => $this->denormalizeRequestBodyToDepositInputType(
                $message->getDepositRequestBody()
            ),
            CreateDepositInputType::class => $this->getDepositInputTypeFromCreateDepositInputType(
                $message->getDepositRequestBody()
            ),
        };
    }

    /**
     * @throws SmartcardDepositRequestValidationErrorException
     */
    private function denormalizeRequestBodyToDepositInputType(array $requestBody): DepositInputType
    {
        /**
         * @var DepositInputType $depositInput
         */
        $depositInput = $this->denormalizeInputType($requestBody, DepositInputType::class);

        return $depositInput;
    }

    /**
     * @throws SmartcardDepositRequestValidationErrorException
     */
    private function getDepositInputTypeFromCreateDepositInputType(array $requestBody): DepositInputType
    {
        /**
         * @var CreateDepositInputType $createDepositInput
         */
        $createDepositInput = $this->denormalizeInputType($requestBody, CreateDepositInputType::class);

        return DepositInputType::create(
            $createDepositInput->getReliefPackageId(),
            null,
            $createDepositInput->getBalanceAfter(),
            $createDepositInput->getCreatedAt()
        );
    }

    private function denormalizeInputType(array $requestBody, string $inputTypeClass): object
    {
        try {
            $inputType = RequestConverter::normalizeInputType($requestBody, $inputTypeClass);
        } catch (NotNormalizableValueException $exception) {
            throw new SmartcardDepositRequestValidationErrorException(
                "Deposit request body could not be denormalized to " . $inputTypeClass . "! " .
                $exception->getMessage()
            );
        }

        $validationErrors = $this->validator->validate($inputType);
        if ($validationErrors->count() > 0) {
            throw new SmartcardDepositRequestValidationErrorException(
                "Deposit request body for " . $inputTypeClass . " is not valid! Errors: " . $validationErrors
            );
        }

        return $inputType;
    }
}
