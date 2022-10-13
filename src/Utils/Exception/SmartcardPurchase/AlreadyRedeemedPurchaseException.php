<?php

declare(strict_types=1);

namespace Utils\Exception\SmartcardPurchase;

use Entity\SmartcardPurchase;

class AlreadyRedeemedPurchaseException extends SmartcardPurchaseException
{
    public function __construct(SmartcardPurchase $smartcardPurchase)
    {
        parent::__construct(
            "Purchase' #{$smartcardPurchase->getId()} was already redeemed at " . $smartcardPurchase->getRedeemedAt(
            )->format(
                'Y-m-d H:i:s'
            )
        );
    }
}
