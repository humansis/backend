<?php

declare(strict_types=1);

namespace Component\Smartcard\Invoice\Exception;

use Entity\SmartcardPurchase;

class AlreadyRedeemedInvoiceException extends NotRedeemableInvoiceException
{
    public function __construct(SmartcardPurchase $smartcardPurchase)
    {
        parent::__construct(
            "Purchase' #{$smartcardPurchase->getId()} was already redeemed at " . $smartcardPurchase->getInvoicedAt(
            )->format(
                'Y-m-d H:i:s'
            )
        );
    }
}
