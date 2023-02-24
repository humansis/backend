<?php

declare(strict_types=1);

namespace Tests\ComponentHelper;

use Component\Smartcard\Messaging\Handler\SmartcardPurchaseMessageHandler;
use Component\Smartcard\SmartcardPurchaseService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Entity\SmartcardPurchase;
use Exception;
use InputType\PurchaseProductInputType;
use InputType\SmartcardPurchaseInputType;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Utils\SmartcardService;

/**
 * @property EntityManagerInterface $em
 */
trait SmartcardPurchaseHelper
{
    /**
     * @throws Exception
     */
    public function createPurchase(
        string $serialNumber,
        SmartcardPurchaseInputType $smartcardPurchaseInputType,
        SmartcardPurchaseService $smartcardPurchaseService
    ): SmartcardPurchase {
        return $smartcardPurchaseService->purchase(
            $serialNumber,
            $smartcardPurchaseInputType
        );
    }

    public static function buildSmartcardPurchaseInputType(
        int $assistanceId,
        int $beneficiaryId,
        int $vendorId,
        PurchaseProductInputType $productInputType
    ): SmartcardPurchaseInputType {
        $purchaseInputType = new SmartcardPurchaseInputType();
        $purchaseInputType->addProduct($productInputType);
        $purchaseInputType->setAssistanceId($assistanceId);
        $purchaseInputType->setBeneficiaryId($beneficiaryId);
        $purchaseInputType->setVendorId($vendorId);
        $purchaseInputType->setCreatedAt(new DateTime());

        return $purchaseInputType;
    }

    public static function buildPurchaseProductInputType(string $currency, float $value): PurchaseProductInputType
    {
        $productInputType = new PurchaseProductInputType();
        $productInputType->setId(1);
        $productInputType->setCurrency($currency);
        $productInputType->setValue($value);

        return $productInputType;
    }

    public static function consumeMessagesFromPurchaseQueue(int $messagesCount): void
    {
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.in_memory');
        $smartcardPurchaseMessageHandler = self::getContainer()->get(SmartcardPurchaseMessageHandler::class);

        $transportMessages = $transport->get();

        for ($i = 0; $i < $messagesCount; $i++) {
            /** @var Envelope $envelope */
            $envelope = $transportMessages[$i];

            try {
                $smartcardPurchaseMessageHandler($envelope->getMessage());
            } catch (Exception) {
            }

            $transport->ack($envelope);
        }
    }
}
