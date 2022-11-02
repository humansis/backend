<?php

declare(strict_types=1);

namespace Tests\ComponentHelper;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Entity\SmartcardPurchase;
use Exception;
use InputType\PurchaseProductInputType;
use InputType\SmartcardPurchaseInputType;
use Symfony\Component\DependencyInjection\Container;
use Utils\SmartcardService;

/**
 * @property Container $container
 * @property EntityManagerInterface $em
 */
trait SmartcardPurchaseHelper
{
    /**
     * @throws Exception
     */
    public function createPurchase(
        string $serialNumber,
        SmartcardPurchaseInputType $smartcardPurchaseInputType
    ): SmartcardPurchase {
        return self::$container->get(SmartcardService::class)->purchase(
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
}
