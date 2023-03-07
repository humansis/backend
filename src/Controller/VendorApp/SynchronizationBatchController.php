<?php

declare(strict_types=1);

namespace Controller\VendorApp;

use Component\Smartcard\Messaging\Message\SmartcardDepositMessage;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\SynchronizationBatch\CreateDepositInputType;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;
use Utils\UserService;

class SynchronizationBatchController extends AbstractVendorAppController
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    #[Rest\Post('/vendor-app/v1/syncs/deposit')]
    public function create(
        Request $request,
        MessageBusInterface $bus,
        UserService $userService
    ): Response {
        $user = $userService->getCurrentUser();
        $batchRequest = $request->request->all();

        foreach ($batchRequest as $depositRequest) {
            try {
                $smartcardDepositMessage = new SmartcardDepositMessage(
                    $user->getId(),
                    CreateDepositInputType::class,
                    $depositRequest['smartcardSerialNumber'] ?? null,
                    $depositRequest
                );

                $bus->dispatch($smartcardDepositMessage);
            } catch (Throwable $throwable) {
                $this->logger->error('Failed to dispatch SmartcardDepositMessage: ' . $throwable->getMessage());
                throw new ServiceUnavailableHttpException(3600, 'Failed to dispatch message.', $throwable);
            }
        }

        return new Response();
    }
}
