<?php
declare(strict_types=1);

namespace Component\Smartcard;

use Entity\Assistance;

/**
 * Create empty (virtual) deposit.
 *
 * It is useful for processing deposits which are not synced into the system.
 */
class EmptySmartcardDeposit
{
    protected $value;

    public function __construct(Assistance $assistance)
    {
        foreach ($assistance->getCommodities() as $commodity) {
            if ('Smartcard' === $commodity->getModalityType()->getName()) {
                $this->value = $commodity->getValue();
                break;
            }
        }
    }

    public function getValue()
    {
        return $this->getValue();
    }
}
