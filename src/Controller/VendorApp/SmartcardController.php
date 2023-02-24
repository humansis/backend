<?php

declare(strict_types=1);

namespace Controller\VendorApp;

use Component\Smartcard\Messaging\Message\SmartcardPurchaseMessage;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Entity\SmartcardBeneficiary;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

class SmartcardController extends AbstractVendorAppController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $logsDir,
        private readonly ManagerRegistry $managerRegistry
    ) {
    }

    #[Rest\Post('/vendor-app/v4/smartcards/{serialNumber}/purchase')]
    public function purchase(Request $request, MessageBusInterface $bus): Response
    {
        try {
            $bus->dispatch(
                new SmartcardPurchaseMessage(
                    $request->get('serialNumber'),
                    $request->request->all()
                )
            );
        } catch (Throwable $throwable) {
            $this->logger->error('Failed to dispatch SmartcardPurchaseMessage: ' . $throwable->getMessage());

            $this->writeData(
                'purchaseV3',
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

    #[Rest\Get('/vendor-app/v1/smartcards/blocked')]
    public function listOfBlocked(Request $request): Response
    {
        $country = $request->headers->get('country');
        $smartcardBeneficiaries = $this->managerRegistry->getRepository(SmartcardBeneficiary::class)->findBlocked(
            $country
        );

        return new JsonResponse($smartcardBeneficiaries);
    }
}
