<?php

declare(strict_types=1);

namespace Tests\ComponentHelper;

use Component\Smartcard\Invoice\InvoiceFactory;
use Doctrine\ORM\EntityManagerInterface;
use Entity\Invoice;
use Entity\User;
use Entity\Vendor;
use Exception;
use InputType\SmartcardInvoiceCreateInputType;
use Symfony\Component\DependencyInjection\Container;

/**
 * @property Container $container
 * @property EntityManagerInterface $em
 */
trait SmartcardInvoiceHelper
{
    /**
     * @throws Exception
     */
    public function createInvoice(
        Vendor $vendor,
        SmartcardInvoiceCreateInputType $invoiceCreateInputType,
        User $user
    ): Invoice {
        return self::$container->get(InvoiceFactory::class)->create($vendor, $invoiceCreateInputType, $user);
    }

    /**
     * @param int[] $purchaseIds
     */
    public static function buildInvoiceCreateInputType(array $purchaseIds): SmartcardInvoiceCreateInputType
    {
        $invoiceCreateInputType = new SmartcardInvoiceCreateInputType();
        $invoiceCreateInputType->setPurchaseIds($purchaseIds);

        return $invoiceCreateInputType;
    }
}
