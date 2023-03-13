<?php

declare(strict_types=1);

namespace Controller\OfflineApp;

use Component\Smartcard\Messaging\Message\SmartcardDepositMessage;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\Smartcard\DepositInputType;
use InputType\SmartcardDepositFilterInputType;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Entity\SmartcardDeposit;
use Repository\SmartcardDepositRepository;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;
use Utils\UserService;

class SmartcardDepositController extends AbstractOfflineAppController
{
    public function __construct(
        private readonly string $logsDir,
        private readonly ManagerRegistry $managerRegistry,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Rest\Get('/offline-app/v1/smartcard-deposits')]
    public function list(Request $request, SmartcardDepositFilterInputType $filter): JsonResponse
    {
        /** @var SmartcardDepositRepository $repository */
        $repository = $this->managerRegistry->getRepository(SmartcardDeposit::class);
        $data = $repository->findByParams($filter);

        $response = $this->json($data);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    #[Rest\Get('/offline-app/v1/last-smartcard-deposit/{id}')]
    public function lastSmartcardDeposit(SmartcardDeposit $smartcardDeposit, Request $request): JsonResponse
    {
        $response = $this->json($smartcardDeposit);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    #[Rest\Post('/offline-app/v5/smartcards/{serialNumber}/deposit')]
    public function deposit(
        Request $request,
        string $serialNumber,
        MessageBusInterface $bus,
        UserService $userService
    ): Response {
        $user = $userService->getCurrentUser();

        try {
            $smartcardDepositMessage = new SmartcardDepositMessage(
                $user->getId(),
                DepositInputType::class,
                $serialNumber,
                $request->request->all()
            );

            $bus->dispatch($smartcardDepositMessage);
        } catch (Throwable $throwable) {
            $this->logger->error('Failed to dispatch SmartcardDepositMessage: ' . $throwable->getMessage());

            $this->writeData(
                'depositV5',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all(), JSON_THROW_ON_ERROR)
            );

            throw new ServiceUnavailableHttpException(3600, 'Failed to dispatch message.', $throwable);
        }

        return new Response();
    }

    private function writeData(string $type, string $user, string $smartcard, $data): void
    {
        $filename = $this->logsDir . '/';
        $filename .= implode('_', ['SC-invalidData', $type, 'vendor-' . $user, 'sc-' . $smartcard . '.json']);
        $logFile = fopen($filename, "a+");
        fwrite($logFile, (string) $data);
        fclose($logFile);
    }
}
